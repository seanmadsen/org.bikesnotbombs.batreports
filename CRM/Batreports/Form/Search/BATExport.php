<?php

/**
 * A custom contact search
 */
class CRM_Batreports_Form_Search_BATExport
extends CRM_Contact_Form_Search_Custom_Base
implements CRM_Contact_Form_Search_Interface {

  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('Bike-A-Thon export'));

    $form->add('text', 'year', ts('Year'));

    // Optionally define default search values
    $date = date_create('now');
    $form->setDefaults(array(
      'year' => date_format($date, 'Y'),
    ));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('year'));
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total' => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    // return by reference
    $columns = array(
      ts('page') => 'page',
      ts('pile') => 'pile',
      ts('cid') => 'cid',
      ts('rnum') => 'rnum',
      ts('last_name') => 'last_name',
      ts('first_name') => 'first_name',
      ts('email') => 'email',
      ts('phone') => 'phone',
      ts('address') => 'address',
      ts('status') => 'status',
      ts('reg_date') => 'reg_date',
      ts('reg_by_contact_id') => 'reg_by_contact_id',
      ts('reg_by_name') => 'reg_by_name',
      ts('drupal_user_name') => 'drupal_user_name',
      ts('route ') => 'route',
      ts('total_is_public') => 'total_is_public',
      ts('total') => 'total',
      ts('pcp_total') => 'pcp_total',
      ts('overdue') => 'overdue',
      ts('fmin') => 'fmin',
      ts('pcp_id') => 'pcp_id',
      ts('pcp_url') => 'pcp_url',
      ts('time_printed') => 'time_printed',
      ts('bat_age') => 'bat_age',
      ts('team_name') => 'team_name',
      ts('emergency_name') => 'emergency_name',
      ts('emergency_phone') => 'emergency_phone',
      ts('prev_years') => 'prev_years',
      ts('max_prev_indiv_t') => 'prev_max_direct',
      ts('note') => 'note'
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql(
        $this->select(),
        $offset,
        $rowcount,
        'page',
        $includeContactIDs,
        "");
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "*";
  }

  /**
   * Create temporary tables based on SQL in a file with the same name as this
   * file (but .sql at the end instead of .php). Values in the sql file are
   * replaced with dynamic values by setting SQL variables before loading the
   * main SQL
   */
  function executePreSQL() {
    // Set SQL variable for the year, as supplied in the search form
    CRM_Core_DAO::executeQuery('set @year = %1;', array(
      1 => array(CRM_Utils_Array::value('year', $this->_formValues), 'Integer'),
    ));

    // Set SQL variable for the current Drupal database name (since we use this
    // to find the Drupal username of riders
    $drupalDB = $GLOBALS['databases']['default']['default']['database'];
    CRM_Core_DAO::executeQuery('set @drupal_table = %1;', array(
      1 => array($drupalDB, 'String'),
    ));

    // Evaluate SQL from file
    $sqlFile = __DIR__ . "/" . basename(__FILE__, '.php') . '.sql';
    $sql = file_get_contents($sqlFile);
    $queries = explode(";", $sql);
    foreach ($queries as $query) {
      if (!empty(trim($query))) {
        CRM_Core_DAO::executeQuery($query);
      }
    }
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    // The use of $GLOBALS here is to make sure we only run executePreSQL once
    // because this gives us some performance gains
    if (empty($GLOBALS['pre_sql_executed'])) {
      $this->executePreSQL();
      $GLOBALS['pre_sql_executed'] = TRUE;
    }

    return "from results";
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    return "";
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $returnSQL
   *
   * @return string
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL, $returnSQL = FALSE) {
    $sql = $this->sql(
      'cid',
      $offset,
      $rowcount,
      $sort
    );

    if ($returnSQL) {
      return $sql;
    }

    return CRM_Core_DAO::composeQuery($sql, CRM_Core_DAO::$_nullArray);
  }

  /**
   * @return null|string
   */
  public function count() {
    return CRM_Core_DAO::singleValueQuery(
      $this->sql('count(distinct cid) as total')
    );
  }

}
