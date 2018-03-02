<?php
	/**
	 * This class deals with all database communication.
	 *  Here's a tip:  Never pet a burning dog.
	 */
class DB {

    private $connection = null;
		public $burningDog = false;

	/**
	 * Instantiates MYSQLI and creates connection.
	 */
    public function __construct() {
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
        mysqli_report(MYSQLI_REPORT_STRICT);
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, defined('DB_PORT') ? DB_PORT : '3306');
            $this->connection->set_charset("utf8");
        } catch (Exception $e) {
			ob_start();
			die('Database Connection Failed');
        }
    }

	/**
	 * Closes connection at destruction of class.
	 */
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

	/**
	 * Executes any query.
	 */
    public function query($query) {

    	// make query and return results
        $results = $this->connection->query($query);

        if ($this->connection->error) {
            $this->log_db_errors($this->connection->error, $query);
            return false;
        } else {
            return $results;
        }
    }


	/**
	 * Mysqli->fetch_object(). Default output is an array of objects. Multi-dimensional array is the other option.
	 * Use:
	 * $stuff = $db->get_data_object('SELECT * FROM vce_test ');
	 * foreach ($stuff as $row) {
	 * echo '<br>';
	 * echo $row->name;
	 * }
	 */
    public function get_data_object($query, $object = true) {
        //set $row var to null
        $output = null;
        $result = $this->connection->query($query);

        //echo '<pre>';
        //echo $query . '<hr>';
       // print_r($result);
       // echo '</pre>';

        if ($this->connection->error) {
            $this->log_db_errors($this->connection->error, $query);
            return false;
        } else {
            $output = array();
            while ($row = ($object) ? $result->fetch_object() : $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        return $output;
    }


    /**
	 * Insert data into database table
	 * This handles most types of insertion into a single table.
	 * This looks to see if it is a single row or multiple rows being inserted and calls
	 * either insert_multi() or insert_single()
	 * for multi:
	 * $stuff = $db->insert('test',
	 * array(
	 * array('name'=>'dayton1', 'email'=>'dayton1@email.com', 'state'=>'WA'),
	 * array('name'=>'dayton2', 'email'=>'dayton2@email.com', 'state'=>'WA'),
	 * array('name'=>'dayton3', 'email'=>'dayton3@email.com', 'state'=>'WA')
	 * )
	 * );
	 * $db->insert('table', $data);
	 * $stuff->lastid 	outputs the last id used
	 * $stuff->insertedids 	outputs an array of all the inserted ids, in order
	 *
	 * for single:
	 * $data = array(
	 * 'name' => 'Thelma',
	 * 'email' => 'thelma@address.com',
	 * 'active' => 1
	 *  )
     */
    public function insert($table, $variables = array()) {
		$table = TABLE_PREFIX . $table;
        //Check for values
        if (empty($variables)) {
        	$this->log_db_errors('variables is empty', 'insert function');
            return false;
        }
        // check if $variables is a multi dimentional array
        if (isset($variables[0])) {
       		$output = array();
        	//insert an array of keys=>values
        	foreach ($variables as $insertion_data) {
        		array_push($output, $this->insert_single($table, $insertion_data));
        	}
        } else {
    		//insert single key=>value
    		$output = $this->insert_single($table, $variables);
        }
		// returns insert id as array
		return $output;
    }

    /**
     *  Example usage:
     *  $user_data = array(
     *  'name' => 'Serio',
     *  'email' => 'email@address.com',
     *  'active' => 1
     *  );
     *  $db->insert( 'users_table', $user_data );
     */
    public function insert_single($table, $data = array()) {
        //Make sure the array isn't empty
        if (empty($data)) {
            $this->log_db_errors('data is empty', 'insert_single function');
            return false;
        }

        $sql = "INSERT INTO " . $table;
        $fields = array();
        $values = array();
        foreach ($data as $field => $value) {
            $fields[] = $field;
            $values[] = "'" . $value . "'";
        }
        $fields = '(' . implode(',', $fields) . ')';
        $values = '(' . implode(',', $values) . ')';

        $sql .= $fields . ' VALUES ' . $values;

        $query = $this->connection->query($sql);

        if ($this->connection->error) {
            //return false;
            $this->log_db_errors($this->connection->error, $sql);
            return false;
        } else {
        	return $this->connection->insert_id;
        }
    }


	/**
     *   Update data in database table
     *
     *   Example usage:
     *   $update = array( 'name' => 'Not bennett', 'email' => 'someotheremail@email.com' );
     *   $update_where = array( 'user_id' => 44, 'name' => 'Bennett' );
     *   $db->update( 'users_table', $update, $update_where, 1 );
     *
     *   if $return_affected_rows == true, this method returns the number of rows affected
     */
    public function update($dbtable, $data = array(), $where = array(), $limit = '', $return_affected_rows = true) {
        $dbtable = TABLE_PREFIX . $dbtable;
		//check to see if data is available
        if (empty($data)) {
            return false;
        }
        $sql = "UPDATE " . $dbtable . " SET ";
        foreach ($data as $field => $value) {
            $updates[] = "$field = '$value'";
        }
        $sql .= implode(', ', $updates);

        //Add the $where clauses as needed
        if (!empty($where)) {
            foreach ($where as $field => $value ) {
                $value = $value;
                $clause[] = "$field = '$value'";
            }
            $sql .= ' WHERE ' . implode(' AND ', $clause);
        }

        if (!empty($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }

        $query = $this->connection->query($sql);

        if ($this->connection->error) {
            $this->log_db_errors($this->connection->error, $sql);
            return false;
        } else {
            $rows = mysqli_affected_rows($this->connection);
            if ($rows > 0) {
            	if ($return_affected_rows) {
            		return $rows;
            	} else {
            		return true;
            	}
            } else {
            	return false;
            }
        }
    }

  	/**
     *  Delete rows from table
     *
     *  Example usage:
     *  $where = array( 'user_id' => 4241, 'email' => 'email@address.com' );
     *  $db->delete( 'table', $where, 1 );
     *
     */
    public function delete($table, $where = array(), $limit = '') {
        $table = TABLE_PREFIX . $table;
        //Delete clauses require a where param, otherwise use "truncate"
        if (empty($where)) {
            return false;
        }

        $sql = "DELETE FROM " . $table;
        foreach ($where as $field => $value) {
            $value = $value;
            $clause[] = "$field = '$value'";
        }
        $sql .= " WHERE " . implode(' AND ', $clause);

        if (!empty($limit)) {
            $sql .= " LIMIT " . $limit;
        }

        $query = $this->connection->query($sql);

        if ($this->connection->error) {
            $this->log_db_errors($this->connection->error, $sql);
            return false;
        } else {
            return true;
        }
	}

    /**
     *  Get last auto-incrementing ID associated with an insertion
     *
     *  Example usage:
     *  $db->insert( 'users_table', $user );
     *  $last = $db->lastid();
     */
    public function lastid() {
        return $this->connection->insert_id;
    }



    /**
     *  Error message handling for dev sites
     */
    public function log_db_errors($error, $query) {
        $message = '<p>Error at ' . date('Y-m-d H:i:s') . ':</p>';
        $message .= '<p>Query: ' . htmlentities($query) . '<br>Error: ' . $error . '</p>';

        if (!defined('VCE_DEBUG') || (defined('VCE_DEBUG') && VCE_DEBUG)) {
            trigger_error($message);
            echo '<pre style="background:#ffc;">';
            print_r($message);
            echo '</pre>';
        }
    }


    /**
     *  sanitize data
     *
     *  example :
     *  $sanitizedData = $db->sanitize( $_POST['data'] );
     *
     *  filter an entire array:
     *  $data = array( 'name' => $_POST['data'], 'email' => 'address' );
     *  $data = $db->sanitize( $data );
     *
     */
     public function sanitize($data) {
         if (!is_array($data)) {
             $data = $this->connection->real_escape_string($data);
             $data = trim(htmlentities($data, ENT_QUOTES, 'UTF-8', false));
         } else {
             //split array and call self to sanitize
             $data = array_map(array($this, 'sanitize'), $data);
         }
         return $data;
     }


	/**
     *  filter using mysqli_real_escape_string
	 */
     public function mysqli_escape($data) {
         if (!is_array($data)) {
             $data = $this->connection->real_escape_string($data);
         } else {
             //split array and call self to escape
             $data = array_map(array($this, 'mysqli_escape'), $data);
         }
         return $data;
     }


	/**
     *  reverse the effects of mysqli_escape and sanitize (if values are stored in sanitized form in the DB, this will bring
     *  them back in the original form they had.)
	 */
     public function clean($data) {
         $data = stripslashes($data);
         $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
         $data = urldecode($data);
         return $data;
     }

}
