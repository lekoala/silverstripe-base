<?php

namespace LeKoala\Base\i18n;

use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayLib;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\Director;
use LeKoala\Base\i18n\TextCollector;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Dev\Tasks\i18nTextCollectorTask;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\i18n\TextCollection\i18nTextCollector;
use Symfony\Component\Yaml\Parser;

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
        $this->addOption("import", "Import existing files", false);
        $this->addOption("module", "Module", null, $modules);
        $options = $this->askOptions();

        $module = $options['module'];
        $import = $options['import'];

        if ($module) {
            if ($import) {
                $this->importTranslations($module);
            }
            $this->exportTranslations($module);
        }
    }

    protected function importTranslations($module)
    {
    }
    protected function exportTranslations($module)
    {
        $langPath = ModuleResourceLoader::resourcePath($module .':lang');
        $fullLangPath = Director::baseFolder() . '/' . str_replace([':','\\'], '/', $langPath);

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
