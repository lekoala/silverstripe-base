<?php

namespace LeKoala\Base\i18n;

use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayLib;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\Director;
use Symfony\Component\Yaml\Parser;
use LeKoala\Base\i18n\TextCollector;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\i18n\Messages\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use SilverStripe\Core\Injector\Injector;
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

    /**
     * @param HTTPRequest $request
     */
    public function init(HTTPRequest $request)
    {
        $modules = ArrayLib::valuekey(array_keys(ModuleLoader::inst()->getManifest()->getModules()));
        $this->addOption("import", "Import translations", false);
        $this->addOption("export", "Export translations", false);
        $this->addOption("module", "Module", null, $modules);
        $options = $this->askOptions();

        $module = $options['module'];
        $import = $options['import'];
        $export = $options['export'];

        if ($module) {
            if ($import) {
                $this->importTranslations($module);
            }
            if ($export) {
                $this->exportTranslations($module);
            }
        } else {
            $this->message("Please select a module");
        }
    }

    protected function getLangPath($module)
    {
        $langPath = ModuleResourceLoader::resourcePath($module .':lang');
        return Director::baseFolder() . '/' . str_replace([':','\\'], '/', $langPath);
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

        $header = array_keys($data[0]);
        $count = count($header);
        $writer = Injector::inst()->create(Writer::class);
        $langs = array_slice($header, 1, $count);
        foreach ($langs as $lang) {
            $entities = [];
            foreach ($data as $row) {
                $entities[$row['key']] = $row[$lang];
            }
            $writer->write(
                $entities,
                $lang,
                dirname($fullLangPath)
            );
        }
    }
    protected function importFromExcel($file, $fullLangPath)
    {
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];
        $i = 0;
        foreach ($worksheet->getRowIterator() as $row) {
            $i++;
            if ($i > 9999) {
                break;
            }
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // This loops through all cells,
            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }
            if (empty($cells)) {
                break;
            }
            $rows[] = $cells;
        }
        //TODO: normalize rows
    }
    protected function importFromCsv($file, $fullLangPath)
    {
        $rows = array_map('str_getcsv', file($file));
        $header = array_shift($rows);
        $count = count($header);
        $data = array();
        foreach ($rows as $row) {
            while (count($row) < $count) {
                $row[] = '';
            }
            $row = array_slice($row, 0, $count);
            $data[] = array_combine($header, $row);
        }
        return $data;
    }
    protected function exportTranslations($module)
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
            $parser = new Parser();
            $data = $parser->parse(file_get_contents($translationFile));

            $declaredLang = key($data);
            $messages = $data[$declaredLang];
            foreach ($messages as $entity => $entityData) {
                foreach ($entityData as $k => $v) {
                    $entityKey = $entity. '.' .  $k;
                    if (!isset($allMessages[ $entityKey])) {
                        $allMessages[$entityKey] = $default;
                    }
                    $allMessages[$entityKey][$i] = $v;
                }
            }
            $i++;
        }
        ksort($allMessages);

        // Write them to a csv file

        $destinationFilename =  str_replace('/lang', '/lang.csv', $fullLangPath);
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
        $this->message("Translations written to $destinationFilename");
    }

    public function isEnabled()
    {
        return Director::isDev();
    }
}
