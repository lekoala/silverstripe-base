<?php

namespace LeKoala\Base\i18n;

use Exception;
use SilverStripe\Dev\Debug;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayLib;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\Director;
use Symfony\Component\Yaml\Parser;
use LeKoala\Base\i18n\TextCollector;
use LeKoala\ExcelImportExport\ExcelImportExport;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\i18n\Messages\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\i18n\Messages\YamlReader;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Dev\Tasks\i18nTextCollectorTask;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\i18n\TextCollection\i18nTextCollector;

/**
 */
class TranslationsImportExportTask extends BuildTask
{
    private static $segment = 'TranslationsImportExportTask';

    protected $title = "Translations import export task";

    protected $description = "Easily import and export translations";

    protected $debug;

    /**
     */
    public function init()
    {
        $request = $this->getRequest();
        $modules = ArrayLib::valuekey(array_keys(ModuleLoader::inst()->getManifest()->getModules()));
        $this->addOption("import", "Import translations", false);
        $this->addOption("export", "Export translations", false);
        $this->addOption("debug", "Show debug output and do not write files", false);
        $this->addOption("excel", "Use excel if possible (require excel-import-export module)", true);
        $this->addOption("module", "Module", null, $modules);
        $options = $this->askOptions();

        $module = $options['module'];
        $import = $options['import'];
        $excel = $options['excel'];
        $export = $options['export'];

        $this->debug = $options['debug'];

        if ($module) {
            if ($import) {
                $this->importTranslations($module);
            }
            if ($export) {
                $this->exportTranslations($module, $excel);
            }
        } else {
            $this->message("Please select a module");
        }
    }

    protected function getLangPath($module)
    {
        $langPath = ModuleResourceLoader::resourcePath($module . ':lang');
        return Director::baseFolder() . '/' . str_replace([':', '\\'], '/', $langPath);
    }

    protected function importTranslations($module)
    {
        $fullLangPath = $this->getLangPath($module);
        $modulePath = dirname($fullLangPath);

        $excelFile = $modulePath . "/lang.xlsx";
        $csvFile = $modulePath . "/lang.csv";

        $data = null;
        if (is_file($excelFile)) {
            $this->message("Importing $excelFile");
            $data = $this->importFromExcel($excelFile, $fullLangPath);
        } elseif (is_file($csvFile)) {
            $this->message("Importing $csvFile");
            $data = $this->importFromCsv($csvFile, $fullLangPath);
        }

        if (!$data) {
            $this->message("No data to import");
            return;
        }

        if ($this->debug) {
            Debug::dump($data);
        }

        $header = array_keys($data[0]);
        $count = count($header);
        $writer = Injector::inst()->create(Writer::class);
        $langs = array_slice($header, 1, $count);
        foreach ($langs as $lang) {
            $entities = [];
            foreach ($data as $row) {
                $key = trim($row['key']);
                if (!$key) {
                    continue;
                }
                $value = $row[$lang];
                if (is_string($value)) {
                    $value = trim($value);
                }
                $entities[$key] = $value;
            }
            if (!$this->debug) {
                $writer->write(
                    $entities,
                    $lang,
                    dirname($fullLangPath)
                );
                $this->message("Imported " . count($entities) . " messages in $lang");
            } else {
                Debug::show($lang);
                Debug::dump($entities);
            }
        }
    }

    protected function importFromExcel($file, $fullLangPath)
    {
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];
        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            // $cellIterator->setIterateOnlyExistingCells(true);
            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }
            if (empty($cells)) {
                break;
            }
            $rows[] = $cells;
        }
        return $this->getDataFromRows($rows);
    }

    /**
     * @param array $rows
     * @return array
     */
    protected function getDataFromRows($rows)
    {
        $header = array_shift($rows);
        $firstKey = $header[0];
        if ($firstKey == 'key') {
            $header[0] = 'key'; // Fix some weird stuff
        }
        $count = count($header);
        $data = array();
        foreach ($rows as $row) {
            while (count($row) < $count) {
                $row[] = '';
            }
            $row = array_slice($row, 0, $count);
            $row = $this->normalizeRow($row);
            $data[] = array_combine($header, $row);
        }
        return $data;
    }

    protected function importFromCsv($file, $fullLangPath)
    {
        $rows = array_map('str_getcsv', file($file));
        return $this->getDataFromRows($rows);
    }

    /**
     * @param array $row
     * @return array
     */
    protected function normalizeRow($row)
    {
        foreach ($row as $idx => $value) {
            if ($idx == 0) {
                continue;
            }
            if (strpos($value, '{"') === 0) {
                $row[$idx] = json_decode($value, JSON_OBJECT_AS_ARRAY);
            }
        }
        return $row;
    }

    protected function exportTranslations($module, $excel = true)
    {
        $fullLangPath = $this->getLangPath($module);

        $translationFiles = glob($fullLangPath . '/*.yml');

        // Collect messages in all lang
        $allMessages = [];
        $headers = ['key'];
        $default = [];
        foreach ($translationFiles as $translationFile) {
            $lang = pathinfo($translationFile, PATHINFO_FILENAME);
            $headers[] = $lang;
            $default[] = '';
        }

        $i = 0;
        foreach ($translationFiles as $translationFile) {
            $lang = pathinfo($translationFile, PATHINFO_FILENAME);
            $reader = new YamlReader;
            $messages = $reader->read($lang, $translationFile);

            foreach ($messages as $entityKey => $v) {
                if (!isset($allMessages[$entityKey])) {
                    $allMessages[$entityKey] = $default;
                }
                // Plurals can be arrays and need to be converted
                if (is_array($v)) {
                    $v = json_encode($v);
                }
                $allMessages[$entityKey][$i] = $v;
            }
            $i++;
        }
        ksort($allMessages);
        if ($this->debug) {
            Debug::show($allMessages);
        }

        // Write them to a file
        if ($excel && class_exists(ExcelImportExport::class)) {
            $ext = 'xlsx';
            $destinationFilename = str_replace('/lang', '/lang.' . $ext, $fullLangPath);
            if ($this->debug) {
                Debug::show("Debug mode enabled : no output will be sent to $destinationFilename");
                die();
            }
            if (is_file($destinationFilename)) {
                unlink($destinationFilename);
            }
            // First row contains headers
            $data = [$headers];
            // Add a row per lang
            foreach ($allMessages as $key => $translations) {
                array_unshift($translations, $key);
                $data[] = $translations;
            }
            ExcelImportExport::arrayToFile($data, $destinationFilename);
        } else {
            $ext = 'csv';
            $destinationFilename = str_replace('/lang', '/lang.' . $ext, $fullLangPath);
            if ($this->debug) {
                Debug::show("Debug mode enabled : no output will be sent to $destinationFilename");
                die();
            }
            if (is_file($destinationFilename)) {
                unlink($destinationFilename);
            }
            $fp = fopen($destinationFilename, 'w');
            // UTF 8 fix
            fprintf($fp, "\xEF\xBB\xBF");
            fputcsv($fp, $headers);
            foreach ($allMessages as $key => $translations) {
                array_unshift($translations, $key);
                fputcsv($fp, $translations);
            }
            fclose($fp);
        }

        $this->message("Translations written to $destinationFilename");
    }

    public function isEnabled()
    {
        return Director::isDev();
    }
}
