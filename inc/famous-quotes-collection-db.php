<?php
/**
 * The Famous Quotes Collection Database Class
 * 
 * @package Famous Quotes
 */

class Famous_Quotes_Collection_DB {

	const PLUGIN_DB_VERSION = '1.0'; 

	private $db, $table_name;

	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
		$this->table_name = "`". $this->db->prefix . "quotescollection`";
	}


	/**
	 * Fetches quote entries from the database
	 *
	 * @param array $args = array()
	 * @see $this->frame_condition() for arguments that can be passed
	 * @return array of quote entries
	 */	
    public function get_quotes_array($args = array()) {
			$sql = "SELECT `quote_id`, `quote`, `author`, `tags`, `public`, `time_added`
			FROM " . $this->table_name;

		if($args) {
			$sql .= $this->frame_condition($args);
		}

		if($quotes = $this->db->get_results($sql, ARRAY_A))
			return $quotes;	
		else
			return array();
	}


	/**
	 * Fetches quote entries from the database and returns the array of 
	 * Quotes_Collection_Quote objects
	 *
	 * @param array $args = array()
	 * @see $this->frame_condition() for arguments that can be passed
	 * @return array of Quotes_Collection_Quote objects
	 */	

	public function get_quotes( $args = array() ) {
		if( $quotes_array = $this->get_quotes_array( $args ) ) {
			$quotes = array();
			foreach( $quotes_array as $quote_data ) {
				$quotes[] = new Quotes_Collection_Quote( $quote_data );
			}
			return $quotes;
		}
		return array();
	}

	/** Fetches a single quote from the database **/
	public function get_quote($args = array()) {
		$args['num_quotes'] = 1;
	   	if($quote_array = $this->get_quotes($args))
			return $quote_array[0];
		else return false;
	}

	/** 
	 * Fetches quote entry with a specific ID 
	 *
	 * @param int $quote_id
	 * @return array the quote entry
	 */
	public function get_quote_with_id($quote_id) {
		return $this->get_quote(array('quote_id' => $quote_id));
	}

	/**
	 * Checks if our Quotes Collection table is found in the database
	 *
	 * @return bool true if found, false if not
	 */
	private function is_table_found() {
	    if($this->db->get_var("SHOW TABLES LIKE '".$this->table_name."'") != $this->table_name)
			return true;
		else return false;

	}

	/**
	 * Validates the quote data before it can be safely stored in the database
	 *
	 * @param array $data the quote data
	 * @return array the validated data
	 */
	private function validate_data($data = array()) {
		if(!$data) return array();
	    global $allowedposttags;

		$quote = wp_kses( stripslashes($data['quote']), $allowedposttags );
		$author = wp_kses( stripslashes($data['author']), array( 'a' => array( 'href' => array(),'title' => array() ) ) ) ;	
		$tags = strip_tags( stripslashes($data['tags']) );
		
		$tags = explode(',', $tags);
		foreach ($tags as $key => $tag)
			$tags[$key] = trim($tag);
		$tags = implode(',', $tags);
		if( !isset( $data['public'] ) || ( isset( $data['public'] ) && $data['public'] == 'no' ) )
			$public = "no";
		else
			$public = "yes";
		$data = compact("quote", "author", "tags", "public");
		return $data;
	}

	
	/**
	 * Function to store a single quote in the db
	 *
	 * @param array $entry the quote data
	 */
	public function put_quote($quote_data = array()) {
		if( is_object($quote_data) ) {
			$quote_data = (array) $quote_data;
		}
	    if(!$quote_data || !$quote_data['quote']) return false;
		if(!$this->is_table_found()) 
			return false;
		$quote_data = $this->validate_data($quote_data);

		extract($quote_data);
		
	    $insert = $this->db->prepare( "INSERT INTO " . $this->table_name .
			"(`quote`, `author`,  `tags`, `public`, `time_added`)" .
			"VALUES (%s, %s, %s, %s, %s, NOW())" , $quote, $author, $source, $tags, $public);	
		
		$result = $this->db->query($insert);

		if( 1 == $result ) {
			return $this->db->insert_id;
		}
		else return $result;
	}

	/**
	 * Function to store a bulk of quote entries. Used by the import
	 * functionality.
	 *
	 * @param array $quotes_data a multidimensional array of quote data
	 */
	public function put_quotes($quotes_data = array()) {
		if(!$quotes_data) return 0;

		$values = array();
		$placeholders = array();

		$insert = "INSERT INTO " . $this->table_name .
			" (`quote`, `author`, `tags`, `public`, `time_added`)" .
			" VALUES ";

		foreach($quotes_data as $quote_data) {
			if( is_object($quote_data) ) {
				$quote_data = (array) $quote_data;
			}
			$quote_data = $this->validate_data($quote_data);

			extract($quote_data);

			array_push($values, $quote, $author,  $tags, $public);

			$placeholders[] = "(%s, %s,  %s, %s, NOW())";
		}

		$insert .= implode(', ', $placeholders);

		$insert = $this->db->prepare($insert, $values);

		return $this->db->query($insert);
	}



	/**
	 * Function to delete a bulk of quotes
	 *
	 * @param array $quote_ids an array of IDs of the entries to be deleted
	 */
	public function delete_quotes($quote_ids) {
		if(!$quote_ids)
			return 0;

		foreach( $quote_ids as $quote_id ) {
			if(! is_numeric($quote_id) )
				return 0;
		}

		$sql = "DELETE FROM ".$this->table_name
			."WHERE quote_id IN (".implode(', ', $quote_ids).")";
		return $this->db->query($sql);
	}



	/**
	 * Counts and returns the number of entries with a particular entries.
	 * If no parameter is passed, counts the total number of entries in DB
	 *
	 * @param array $condition
	 * @return int
	 */
	public function count($condition = array())
	{	
		$sql = "SELECT COUNT(*) FROM " . $this->table_name;
		if($condition)
			$sql .= $this->frame_condition($condition);
		$count = $this->db->get_var($sql);
		return $count;
	}


	public static function install_db() {

		if( 
			( ! current_user_can( 'activate_plugins' ) )
			|| (
				$options = get_option('quotescollection')
				&& isset( $options['db_version'] )
				&& self::PLUGIN_DB_VERSION == $options['db_version'] 
			)
		) {
			return;
		}	

		global $wpdb;

		$table_name = $wpdb->prefix.'quotescollection';

		if(!defined('DB_CHARSET') || !($db_charset = DB_CHARSET))
			$db_charset = 'utf8';
		$db_charset = "CHARACTER SET ".$db_charset;
		if(defined('DB_COLLATE') && $db_collate = DB_COLLATE) 
			$db_collate = "COLLATE ".$db_collate;


		// if table name already exists
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
	   		$wpdb->query("ALTER TABLE `{$table_name}` {$db_charset} {$db_collate}");

	   		$wpdb->query("ALTER TABLE `{$table_name}` MODIFY quote TEXT {$db_charset} {$db_collate}");

	   		$wpdb->query("ALTER TABLE `{$table_name}` MODIFY author VARCHAR(255) {$db_charset} {$db_collate}");

	   		if(!($wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'tags'"))) {
	   			$wpdb->query("ALTER TABLE `{$table_name}` ADD `tags` VARCHAR(255) {$db_charset} {$db_collate} AFTER `author`");
			}
	   		if(!($wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'public'"))) {
	   			$wpdb->query("ALTER TABLE `{$table_name}` CHANGE `visible` `public` enum('yes', 'no') DEFAULT 'yes' NOT NULL");
			}
		}
		else {
			//Creating the table ... fresh!
			$sql = "CREATE TABLE " . $table_name . " (
				quote_id MEDIUMINT NOT NULL AUTO_INCREMENT,
				quote TEXT NOT NULL,
				author VARCHAR(255),
				tags VARCHAR(255),
				public enum('yes', 'no') DEFAULT 'yes' NOT NULL,
				time_added datetime NOT NULL,
				time_updated datetime,
				PRIMARY KEY  (quote_id)
			) {$db_charset} {$db_collate};";
			$results = $wpdb->query( $sql );
		}
		
		$options['db_version'] = self::PLUGIN_DB_VERSION;
		update_option('quotescollection', $options);

	}


	public static function uninstall_db() {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}quotescollection" );
	}



} 

?>