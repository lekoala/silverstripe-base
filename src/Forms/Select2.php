<?php

namespace LeKoala\Base\Forms;

use SilverStripe\ORM\DB;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataObject;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use LeKoala\Base\View\CommonRequirements;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Manifest\ModuleResource;

/**
 */
trait Select2
{
    use ConfigurableField;

    /**
     * @config
     * @var array
     */
    private static $allowed_actions = [
        'autocomplete'
    ];

    /**
     * Override locale. If empty will default to current locale
     *
     * @var string
     */
    protected $locale = null;

    /**
     * Multiple values
     *
     * @var boolean
     */
    protected $multiple = false;

    /**
     * Callback to create tags
     *
     * @var Callable
     */
    protected $onNewTag = null;

    /**
     * Ajax class
     *
     * @var string
     */
    protected $ajaxClass;

    /**
     * Ajax where
     *
     * @var string|array
     */
    protected $ajaxWhere;

    /**
     * @var string
     */
    protected $customSearchField;

    /**
     * @var array
     */
    protected $customSearchCols;

    /**
     * @config
     * @var string
     */
    private static $version = '4.1.0-rc.0';

    /**
     * @config
     * @var boolean
     */
    private static $enable_requirements = true;

    /**
     * @config
     * @var bool
     */
    private static $use_cdn = false;

    public function Type()
    {
        return 'select2';
    }

    public function extraClass()
    {
        return 'select no-chosen ' . parent::extraClass();
    }

    public function getServerVars()
    {
        return [
            'queryParam' => 'q',
            'dataKey' => 'data',
            'valueField' => 'id',
            'labelField' => 'text',
        ];
    }

    public function setValue($value, $data = null)
    {
        // For ajax, we need to add the option to the list
        if ($value && $this->getAjaxClass()) {
            $class = $this->getAjaxClass();
            $record = DataObject::get_by_id($class, $value);
            $this->addRecordToSource($record);
        }
        $result = parent::setValue($value, $data);
        return $result;
    }

    public function setSubmittedValue($value, $data = null)
    {
        return $this->setValue($value, $data);
    }

    /**
     * Add a record to the source
     *
     * Useful for ajax scenarios where the list is not prepopulated but still needs to display
     * something on first load
     *
     * @param DataObject $record
     * @return boolean true if the record has been added, false otherwise
     */
    public function addRecordToSource($record)
    {
        if (!$record) {
            return false;
        }
        $source = $this->getSource();
        // It's already in the source
        if (isset($source[$record->ID])) {
            return false;
        }
        $row = [$record->ID => $record->getTitle()];
        // If source is empty, it's not going to be merged properly
        if (!empty($source)) {
            $source = array_merge($row, $source);
        } else {
            $source = $row;
        }
        $this->setSource($source);
        return true;
    }

    /**
     * @link https://github.com/select2/select2/issues/3387
     */
    public function performReadonlyTransformation()
    {
        /** @var Select2SingleField $field */
        $field = $this->castedCopy(Select2SingleField::class);
        $field->setSource($this->getSource());
        $field->setReadonly(true);
        // Required to properly set value if no source set
        if ($this->ajaxClass) {
            $field->setAjaxClass($this->getAjaxClass());
        }
        // This rely on styles in admin.css
        return $field;
    }

    public function getTags()
    {
        return $this->getConfig('tags');
    }

    public function setTags($value)
    {
        return $this->setConfig('tags', $value);
    }

    public function getPlaceholder()
    {
        return $this->getConfig('placeholder');
    }

    public function setPlaceholder($value)
    {
        return $this->setConfig('placeholder', $value);
    }

    public function getAllowClear()
    {
        return $this->getConfig('allowClear');
    }

    public function setAllowClear($value)
    {
        // won't clear without a source
        if ($value) {
            if (!$this->source) {
                $this->setSource(['' => '']);
            }
        }
        return $this->setConfig('allowClear', $value);
    }

    public function getTokenSeparators()
    {
        return $this->getConfig('tokenSeparators');
    }

    public function setTokenSeparator($value)
    {
        return $this->setConfig('tokenSeparators', $value);
    }

    public function getAjax()
    {
        return $this->getConfig('ajax');
    }

    public function setAjax($url, $opts = [])
    {
        $ajax = array_merge([
            'url' => $url,
        ], $opts);
        return $this->setConfig('ajax', $ajax);
    }

    /**
     * Define a callback that returns the results as a map of id => title
     *
     * @param string $class
     * @param string|array $where
     * @return $this
     */
    public function setAjaxWizard($class, $where = null)
    {
        $this->ajaxClass = $class;
        $this->ajaxWhere = $where;
        return $this;
    }

    /**
     * Get ajax where
     *
     * @return string
     */
    public function getAjaxWhere()
    {
        return $this->ajaxWhere;
    }

    /**
     * Set ajax where
     *
     * @param string $ajaxWhere
     * @return $this
     */
    public function setAjaxWhere($ajaxWhere)
    {
        $this->ajaxWhere = $ajaxWhere;
        return $this;
    }

    /**
     * Get ajax class
     *
     * @return string
     */
    public function getAjaxClass()
    {
        return $this->ajaxClass;
    }

    /**
     * Set ajax class
     *
     * @param string $ajaxClass  Ajax class
     * @return $this
     */
    public function setAjaxClass(string $ajaxClass)
    {
        $this->ajaxClass = $ajaxClass;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAjax()
    {
        return $this->ajaxClass || $this->getConfig('ajax');
    }

    public function autocomplete(HTTPRequest $request)
    {
        if ($this->isDisabled() || $this->isReadonly()) {
            return $this->httpError(403);
        }

        // CSRF check
        $token = $this->getForm()->getSecurityToken();
        if (!$token->checkRequest($request)) {
            return $this->httpError(400, "Invalid token");
        }

        $name = $this->getName();
        $term = '%' . $request->getVar('term') . '%';

        $class = $this->ajaxClass;

        $sng = $class::singleton();
        $baseTable = $sng->baseTable();

        // Make a fast query to the table without orm overhead
        $searchField = 'Title';

        // Ensure field exists, this is really rudimentary
        $db = $class::config()->db;
        if (!isset($db[$searchField])) {
            $searchField = 'Name';
        }
        if (!isset($db[$searchField])) {
            $searchField = 'Surname';
        }
        if (!isset($db[$searchField])) {
            $searchField = 'Email';
        }
        if (!isset($db[$searchField])) {
            $searchField = 'ID';
        }
        $searchCols = [$searchField];

        // For members, do something better
        if ($baseTable == 'Member') {
            $searchField = "CONCAT(FirstName,' ',Surname)";
            $searchCols = ['FirstName', 'Surname', 'Email'];
        }

        if ($this->customSearchField) {
            $searchField = $this->customSearchField;
        }
        if ($this->customSearchCols) {
            $searchCols = $this->customSearchCols;
        }

        $sql = 'SELECT ID AS id, ' . $searchField . ' AS text FROM ' . $baseTable . ' WHERE ';

        // Make sure at least one field is not null...
        $parts = [];
        foreach ($searchCols as $searchCol) {
            $parts[] = $searchCol . ' IS NOT NULL';
        }
        $sql .= '(' . implode(' OR ', $parts) . ')';
        // ... and matches search term ...
        $parts = [];
        foreach ($searchCols as $searchCol) {
            $parts[] = $searchCol . ' LIKE ?';
        }
        $sql .= ' AND (' . implode(' OR ', $parts) . ')';
        // ... and any user set requirements
        $where = $this->ajaxWhere;
        foreach ($searchCols as $searchCol) {
            // add one parameter per search col
            $params[] = $term;
        }
        if (is_array($where)) {
            if (ArrayLib::is_associative($where)) {
                $newWhere = [];
                foreach ($where as $col => $param) {
                    // For array, we need a IN statement with a ? for each value
                    if (is_array($param)) {
                        $prepValue = [];
                        foreach ($param as $paramValue) {
                            $params[] = $paramValue;
                            $prepValue[] = "?";
                        }
                        $newWhere[] = "$col IN (" . implode(',', $prepValue) . ")";
                    } else {
                        $params[] = $param;
                        $newWhere[] = "$col = ?";
                    }
                }
                $where = $newWhere;
            }
            $where = implode(' AND ', $where);
        }
        if ($where) {
            $sql .= " AND $where";
        }
        $query = DB::prepared_query($sql, $params);
        $results = iterator_to_array($query);

        $more = false;
        $body = json_encode(['results' => $results, 'pagination' => ['more' => $more]]);

        $response = new HTTPResponse($body);
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Return a link to this field.
     *
     * @param string $action
     * @return string
     */
    public function Link($action = null)
    {
        return Controller::join_links($this->form->FormAction(), 'field/' . $this->getName(), $action);
    }

    /**
     * Get locale to use for this field
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ?: i18n::get_locale();
    }

    /**
     * Determines the presented/processed format based on locale defaults,
     * instead of explicitly setting {@link setDateFormat()}.
     * Only applicable with {@link setHTML5(false)}.
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return Callable
     */
    public function getOnNewTag()
    {
        return $this->onNewTag;
    }

    /**
     * The callback should return the new id
     *
     * @param Callable $locale
     * @return $this
     */
    public function setOnNewTag($callback)
    {
        $this->onNewTag = $callback;
        return $this;
    }


    /**
     * Get the value of customSearchField
     *
     * @return string
     */
    public function getCustomSearchField(): string
    {
        return $this->customSearchField;
    }

    /**
     * Set the value of customSearchField
     *
     * It must be a valid sql expression like CONCAT(FirstName,' ',Surname)
     *
     * This will be the label returned by the autocomplete
     *
     * @param string $customSearchField
     * @return $this
     */
    public function setCustomSearchField(string $customSearchField)
    {
        $this->customSearchField = $customSearchField;
        return $this;
    }

    /**
     * Get the value of customSearchCols
     *
     * @return array
     */
    public function getCustomSearchCols()
    {
        return $this->customSearchCols;
    }

    /**
     * Set the value of customSearchCols
     *
     * @param array $customSearchCols
     * @return $this
     */
    public function setCustomSearchCols(array $customSearchCols)
    {
        $this->customSearchCols = $customSearchCols;
        return $this;
    }

    public function Field($properties = array())
    {
        // Set lang based on locale
        $lang = substr($this->getLocale(), 0, 2);
        if ($lang != 'en') {
            $this->setConfig('language', $lang);
        }

        if ($this->isDisabled() || $this->isReadonly()) {
            $this->setConfig('disabled', true);
        }

        // Set RTL
        $dir = i18n::get_script_direction($this->getLocale());
        if ($dir == 'rtl') {
            $this->setConfig('dir', $dir);
        }

        $ctrl = Controller::curr();
        // @link https://github.com/ttskch/select2-bootstrap4-theme
        if ($ctrl->hasMethod("UseBootstrap4") && $ctrl->UseBootstrap4()) {
            $this->setConfig('theme', 'bootstrap4');
        }
        // @link https://apalfrey.github.io/select2-bootstrap-5-theme/
        if ($ctrl->hasMethod("UseBootstrap5") && $ctrl->UseBootstrap5()) {
            $this->setConfig('theme', 'bootstrap-5');
        }

        // Ajax wizard, needs a form to get controller link
        if ($this->ajaxClass) {
            $token = $this->getForm()->getSecurityToken()->getValue();
            $url = $this->Link('autocomplete') . '?SecurityID=' . $token;
            $this->setAjax($url);
        }

        $this->setAttribute('data-mb-options', $this->getConfigAsJson());
        $this->setAttribute('data-mb', 'select2');

        if (self::config()->enable_requirements) {
            $version = self::config()->version;
            $use_cdn = self::config()->use_cdn;

            if ($use_cdn) {
                $cdnBase = "https://cdn.jsdelivr.net/npm/select2@$version/dist";
            } else {
                $cdnBase = dirname(dirname(self::moduleResource("javascript/vendor/cdn/select2/js/select2.min.js")->getRelativePath()));
            }
            Requirements::css("$cdnBase/css/select2.min.css");

            // Some custom styles for the cms
            if ($ctrl instanceof LeftAndMain) {
                Requirements::css('base/css/Select2Field.css');
            }

            if ($ctrl->hasMethod("UseBootstrap4") && $ctrl->UseBootstrap4()) {
                Requirements::css("https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1/dist/select2-bootstrap4.min.css");
            }
            if ($ctrl->hasMethod("UseBootstrap5") && $ctrl->UseBootstrap5()) {
                Requirements::css("https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1/dist/select2-bootstrap-5-theme.min.css");
            }

            Requirements::javascript("$cdnBase/js/select2.min.js");
            if ($lang != 'en') {
                Requirements::javascript("$cdnBase/js/i18n/$lang.js");
            }
            CommonRequirements::modularBehaviour();
            Requirements::javascript('base/javascript/fields/Select2Field.js');
        }

        return parent::Field($properties);
    }

    /**
     * Helper to access this module resources
     *
     * @param string $path
     * @return ModuleResource
     */
    public static function moduleResource($path)
    {
        return ModuleLoader::getModule('lekoala/silverstripe-base')->getResource($path);
    }


    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        // Tags can be created on the fly and cannot be validated
        if ($this->getTags()) {
            return true;
        }

        if ($this->isAjax()) {
            return true;
        }

        return parent::validate($validator);
    }
}
