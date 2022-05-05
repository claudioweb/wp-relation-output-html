<?php

namespace WpRloutHtml\Essentials;

/**
 * Create native admin list table
 * @author Lorde Aleister <lordealeister@gmail.com>
 * @see http://drzaus.com/snippet/creating-native-admin-tables-in-wordpress-with-reusable-class
 * @example
 * new AdminListTable(
 * 		$display_items, // Items or table name
 * 		// WP_List_Table properties
 * 		array(
 *			'singular' => 'Singular',
 *			'plural'   => 'Plural',
 *		),
 *		// WP_List_Table config
 * 	 	array(
 *       	'columns' => $columnsData,
 *          'orderby' => 'key', // Oderby field
 *          'order' => 'desc', // 'asc' or 'desc'
 * 			// Search fields. Empty to disable search
 *          'search' => array(
 *          	'field_1',
 *              'field_2',
 *        	),
 * 			// Sortable fields. Empty enables sorting in all fields
 *          'sortable' => array(
 *          	'key_1' => 'key_1',
 *              'key_2' => 'key_2',
 *          ),
 *          'per_page' => 40, // Items per page
 * 		)
 * );
 * @endexample
 */
class AdminListTable extends \WP_List_Table {

	private $config;
	private $table;
	private $columns;
	private $sortable;

	/**
	 * __construct Constructor, we override the parent to pass our own arguments.
	 * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX
	 *
	 * @param  mixed $dataOrTable The data to display or table name
	 * @param  mixed $properties The default labels and other WP_List_Table properties to override
	 * @param  mixed $config The WP_List_Table configs
	 * @return void
	 */
	function __construct($dataOrTable, $properties = array(), $config = array()) {
		$properties = wp_parse_args($properties, array(
			'singular'=> 'wp_list_text_link', // singular label
			'plural' => 'wp_list_text_links', // plural label, also this well be one of the table css class
			'ajax'	=> false 				  // we won't support Ajax for this table
		));
		parent::__construct($properties);

		$this->config = wp_parse_args(
            $config,
            array(
			    'top' => '',
			    'bottom' => '',
			    'columns' => array(),	// display columns
			    'sortable' => array(),	// sortable columns
                'per_page' => 10,		// how many per pagination
                'search' => array(),    // fields for search
		));

		if(is_array($dataOrTable))
			$this->items = $dataOrTable;
		else {
			$this->table = $dataOrTable;

			foreach($this->config['columns'] as $column => $value)
				$this->columns .= " " . $column . ",";

			$this->columns = substr($this->columns, 0, -1);
		}

		foreach(isset($this->config['sortable']) && !empty($this->config['sortable']) ? $this->config['sortable'] : $this->config['columns'] as $column => $value)
			$this->sortable[$column] = array($column, false);

        $this->prepare_items();
        echo "<form method='GET'>";

        if(!empty($this->config['search'])) {
            echo "<input type='hidden' name='page' value='" . $_REQUEST['page'] . "'>";
            $this->search_box('Buscar', 'search_id');
        }

        $this->display();

        echo "</form>";
	}

	/**
	 * extra_tablenav Add extra markup in the toolbars before or after the list
	 *
	 * @param  string $which Helps you decide if you add the markup after (bottom) or before (top) the list
	 * @return void
	 */
	function extra_tablenav($which) {
		if($which == "top")
			echo $this->config['top']; // the code that goes before the table is here
		if($which == "bottom")
			echo $this->config['bottom']; // the code that goes after the table is there
	}

	/**
	 * get_columns Define the columns that are going to be used in the table
	 *
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns(): array {
		return $this->config['columns'];
	}

	/**
	 * get_sortable_columns Decide which columns to activate the sorting functionality on
	 *
	 * @return array $sortable, the array of columns that can be sorted by the user; given as $ui_column_name => $datasource_column_name
	 */
	public function get_sortable_columns(): array {
		return $this->sortable;
	}

	/**
	 * prepare_items Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @return object Items to be displayed
	 */
	function prepare_items() {
		global $wpdb, $_wp_column_headers;
		$screen = get_current_screen();

		// parameters that are going to be used to order the result
		$orderby = !empty($_GET["orderby"]) ? ($_GET["orderby"]) : $this->config['orderby'];
		$order = !empty($_GET["order"]) ? ($_GET["order"]) : $this->config['order'];
		// how many to display per page?
		$perpage = $this->config['per_page'];
		// which page is this?
		$paged = $this->get_pagenum();
		// page number
		if(empty($paged) || !is_numeric($paged) || $paged <= 0)
			$paged = 1;

		// adjust the query to take pagination into account
		if(!empty($paged) && !empty($perpage))
			$offset = ($paged - 1) * $perpage;

		$search = '';
		if(!empty($_REQUEST['s'])):
			$like = " LIKE '%" . esc_sql($wpdb->esc_like($_REQUEST['s'])) . "%'";

			foreach($this->config['search'] as $index => $value)
				$search .= $index == 0 ? "AND {$value} {$like}" : " OR {$value} {$like}";
		endif;

		if(!empty($this->table))
			$this->items = $wpdb->get_results("SELECT {$this->columns} FROM {$this->table} WHERE 1=1 {$search}" . $wpdb->prepare("ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d;", $perpage, $offset));

		$this->items = apply_filters('list_table_items', $this->items);

		// register the Columns
		$columns = $this->get_columns();
		$_wp_column_headers[$screen->id] = $columns;
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		// number of elements in your table?
		$totalitems = !empty($this->table) ? $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE 1 = 1 {$search};") : count($this->items);
		// how many pages do we have in total?
		$totalpages = ceil($totalitems / $perpage);

		$this->_column_headers = array($columns, $hidden, $sortable);

		// register the pagination -- */
		$this->set_pagination_args( array(
			'total_items' => $totalitems,
			'total_pages' => $totalpages,
			'per_page' => $perpage,
		)); // the pagination links are automatically built according to those parameters

		// paginate items
		if(empty($this->table))
			$this->items = array_slice($this->items, $offset, $perpage);

		return $this; // chaining
	}

	/**
	 * display_rows Display the rows of records in the table
	 *
	 * @return void echo the markup of the rows
	 */
	function display_rows() {
		$records = &$this->items; // get the records registered in the prepare_items method
		list($columns, $hidden) = $this->get_column_info(); // get the columns registered in the get_columns and get_sortable_columns methods

		if(!empty($records)): // loop for each record
            foreach($records as $id => $rec):
                echo "<tr id='record_" . esc_attr($id) . "'>"; // open the line

				foreach($columns as $column_name => $column_display_name):
					// style attributes for each col
                    $class = "class='$column_name column-$column_name'";
                    $style = "";

					if(in_array($column_name, $hidden))
						$style = ' style="display: none;"';

                    $attributes = $class . $style;

                    $value = is_array($rec) ? $rec[$column_name] : $rec->$column_name;

                    echo "<td {$attributes}>{$value}</td>";
				endforeach;

                echo "</tr>"; // close the line
            endforeach;
		endif;
    }

}