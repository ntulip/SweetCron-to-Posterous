<?php
// Author: Emilio Cavazos
// Date:   4/21/2007
// Notes:  Encapsulate mysql functionality (simple code for simple tasks)
//
// ==============
// example usage
// ==============
//
// // contact db parameters
// $contact['first_name'] = $_POST['first_name'];
// $contact['last_name'] = $_POST['last_name'];
// $contact['office_phone'] = $_POST['office_phone'];
// $contact['mobile_phone'] = $_POST['mobile_phone'];
// $contact['email'] = $_POST['email'];

// // insert contact
// $dal->updateById('contacts', $contact, 'id', $_GET['con_id']);

class DataAccessLayer
{
    // connection variables 
    private $_server = 'localhost';
    private $_username = 'root';
    private $_password = '';
    private $_database = 'db';
    public  $debug = false;

    // connection
    private $_conn;

    function __construct($server,$username,$password,$database) {
	
		$this->_server = $server;
		$this->_username = $username;
		$this->_password = $password;
		$this->_database = $database;
		
        $this->_conn = new mysqli($this->_server, $this->_username, $this->_password, $this->_database);

        // check connection
        if (mysqli_connect_errno()) {
            printf('Connect failed: %s\n', mysqli_connect_error());
            exit();
        }

        // change character set to utf8
        if (!$this->_conn->set_charset('utf8')) {
            printf('Error loading character set utf8: %s\n', $this->_conn->error);
        }
    }

    function __destruct() {
        $this->_conn->close();
    }
    
    // print error
    public function error() {
        return $this->_conn->errno . ': ' . $this->_conn->error;
    }
    
    // count all rows - return int
    public function totalRows($field, $table) {
        $sql = 'select ' . $field . ' from '
        . $table;
        
        $result = $this->_conn->query($sql);

        // execute query
        return $result->num_rows;
    }

    public function query($sql) {
        // output sql sting for debugging
		// crude debugging
        if($this->debug) {
            echo '<h3>Query</h3>';
            echo '<div>';
            echo $sql;
            echo '</div>';
        }

        // execute query
        return $this->_conn->query($sql);
    }
    
    public function queryLimit($sql, $page, $pageCount) {
        $sql .= ' limit ' . $page . ', ' . $pageCount;
        
        // execute query
        return $this->query($sql);
    }

    public function nonQuery($sql) {
        // execute query
        $this->query($sql);

        return $this->_conn->affected_rows;
    }

    public function select($table) {
        $sql = 'select * from '
        . $table;

        // execute query
        return $this->query($sql);
    }

    public function selectFields($table, $parameters) {
        $sql = 'select ';

        // build column names
        foreach ($parameters as $key => $value) {
            $sql .= $value;

            if($key != end(array_keys($parameters))){
                $sql .= ', ';
            }
        }

        $sql .= ' from ' . $table;

        // execute query
        return $this->query($sql);
    }
    
    public function selectById($table, $idName, $idValue) {
            $sql = 'select * from '
            . $table
            . ' where '
            . $idName
            . ' = '
            . $idValue;
            
            // execute query
            return $this->query($sql);
    }

    public function selectByIdOrder($table, $idName, $idValue, $order) {
            $sql = 'select * from '
            . $table
            . ' where '
            . $idName
            . ' = '
            . $idValue
            . ' order by ' . $order;
            
            // execute query
            return $this->query($sql);
    }

    public function selectWhere($table, $parameters, $where) {
            $sql = 'select ';
        
            // build column names
            foreach ($parameters as $key => $value) {
                    $sql .= $value;
    
                    if($key != end(array_keys($parameters))){
                            $sql .= ', ';
                    }
            }
    
            $sql .= ' from ' . $table
            .= ' where ' . $where;

            // execute query
            return $this->query($sql);
    }
    
    public function selectWhereOrder($table, $where, $order) {
        $sql = 'select *'
             . ' from ' . $table
             . ' where ' . $where
             . ' order by ' . $order;

        // execute query
        return $this->query($sql);
    }
    
    public function selectFieldsWhereOrder($table, $parameters, $where, $order) {
        $sql = 'select ';
        
        // build column names
        foreach ($parameters as $key => $value) {
            $sql .= $value;

            if($key != end(array_keys($parameters))){
                    $sql .= ', ';
            }
        }

        $sql .= ' from ' . $table
        . ' where ' . $where
        . ' order by ' . $order;

        // execute query
        return $this->query($sql);
    }

    public function selectOrder($table, $order) {
        $sql = 'select * from '
        . $table
        . ' order by ' . $order;

        // execute query
        return $this->query($sql);
    }

    public function selectFieldsOrder($table, $parameters, $order) {
        $sql = 'select ';
    
        // build column names
        foreach ($parameters as $key => $value) {
            $sql .= $value;

            if($key != end(array_keys($parameters))){
                $sql .= ', ';
            }
        }

        $sql .= ' from ' . $table
        .= ' order by ' . $order;

        // execute query
        return $this->query($sql);
    }
    
    //public function selectWhereOrder($table, $parameters, $where, $order)
    
    // search query
    public function search($table, $fieldsToSearch, $search) {
            $searchWords = explode(' ', $search);
            
            $sql = 'select *';

            $sql .= ' from ' . $table . ' where ';
            
            // search columns for a match
            foreach($searchWords as $wKey => $wValue) {
                    $sql .= '(';
                            
                    foreach ($fieldsToSearch as $key => $value) {
                            $sql .= $value . ' like \'%' . $wValue . '%\'';
                            
                            if($key != end(array_keys($fieldsToSearch))){
                                    $sql .= ' or ';
                            }
                    }
    
                    if($wKey != end(array_keys($searchWords))){
                            $sql .= ') and ';
                    } else {
                            $sql .= ')';
                    }
            }
            
            // execute query
            return $this->query($sql);
    }
    
    // search by fields query
    public function searchFields($table, $fields, $fieldsToSearch, $search) {
            $searchWords = explode(' ', $search);
            
            $sql = 'select ';
    
            // build column names
            foreach ($fields as $key => $value) {
                    $sql .= $value;
    
                    if($key != end(array_keys($fields))){
                            $sql .= ', ';
                    }
            }
    
            $sql .= ' from ' . $table . ' where ';
            
            // search columns for a match
            foreach($searchWords as $wKey => $wValue) {
                    $sql .= '(';
                            
                    foreach ($fieldsToSearch as $key => $value) {
                            $sql .= $value . ' like \'%' . $wValue . '%\'';
                            
                            if($key != end(array_keys($fieldsToSearch))){
                                    $sql .= ' or ';
                            }
                    }
    
                    if($wKey != end(array_keys($searchWords))){
                            $sql .= ') and ';
                    } else {
                            $sql .= ')';
                    }
            }
            
            // execute query
            return $this->query($sql);
    }
    
    // search query
    public function searchKeyConstrain($table, $fieldsToSearch, $search, $keyId, $keyValue) {
            $searchWords = explode(' ', $search);
            
            $sql = 'select *';

            $sql .= ' from ' . $table . ' where ';
            
            // search columns for a match
            foreach($searchWords as $wKey => $wValue) {
                    $sql .= '(';
                            
                    foreach ($fieldsToSearch as $key => $value) {
                            $sql .= $value . ' like \'%' . $wValue . '%\'';
                            
                            if($key != end(array_keys($fieldsToSearch))){
                                    $sql .= ' or ';
                            }
                    }
    
                    if($wKey != end(array_keys($searchWords))){
                            $sql .= ') and ';
                    } else {
                            $sql .= ')';
                    }
            }
            
            $sql .= ' and ' . $keyId . ' = ' . $keyValue;
            
            // execute query
            return $this->query($sql);
    }
    
    // search custom query
    public function searchQuery($sql, $fieldsToSearch, $search) {
            $searchWords = explode(' ', $search);
            
            $sql .= ' where ';
            
            // search columns for a match
            foreach($searchWords as $wKey => $wValue) {
                    $sql .= '(';
                            
                    foreach ($fieldsToSearch as $key => $value) {
                            $sql .= $value . ' like \'%' . $wValue . '%\'';
                            
                            if($key != end(array_keys($fieldsToSearch))){
                                    $sql .= ' or ';
                            }
                    }
    
                    if($wKey != end(array_keys($searchWords))){
                            $sql .= ') and ';
                    } else {
                            $sql .= ')';
                    }
            }
            
            // execute query
            return $this->query($sql);
    }
    
    // search custom query
    public function searchQueryOrder($sql, $fieldsToSearch, $search, $order) {
            $searchWords = explode(' ', $search);
            
            $sql .= ' where ';
            
            // search columns for a match
            foreach($searchWords as $wKey => $wValue) {
                    $sql .= '(';
                            
                    foreach ($fieldsToSearch as $key => $value) {
                            $sql .= $value . ' like \'%' . $wValue . '%\'';
                            
                            if($key != end(array_keys($fieldsToSearch))){
                                    $sql .= ' or ';
                            }
                    }
    
                    if($wKey != end(array_keys($searchWords))){
                            $sql .= ') and ';
                    } else {
                            $sql .= ')';
                    }
            }
            
            $sql .= $order;

            // execute query
            return $this->query($sql);
    }
    
    // todo: add trim function to values
    public function insert($table, $parameters) {
                                            
            $sql = 'insert into '
            . $table
            . ' (';
    
            // build column names
            foreach ($parameters as $key => $value) {
                    $sql .= $key;

                    if($key != end(array_keys($parameters))){
                            $sql .= ', ';
                    }
            }

            $sql .= ') values (';
    
            // build values for columns
            foreach ($parameters as $key => $value) {
                    $sql .= '\'' . $value . '\'';
    
                    if($key != end(array_keys($parameters))){
                            $sql .= ', ';
                    }
            }

            $sql .= ') ';
    
            // execute query
            $this->query($sql);

            return $this->_conn->insert_id;
    }

    public function insertQuery($sql) {
            // execute query
            $this->query($sql);
            
            return $this->_conn->insert_id;
    }

    //public function insertSafe($table, $parameters, $types)
    
    // todo: add trim function to values
    public function updateById($table, $parameters, $idName, $idValue) {                                    
        $sql = 'update '
        . $table
        . ' set ';

        // build column value pairs
        foreach ($parameters as $key => $value) {
                $sql .= $key . ' = \'' . $value . '\'';

                if($key != end(array_keys($parameters))){
                        $sql .= ', ';
                }
        }

        $sql .= ' where '
        . $idName . ' = ' . $idValue;

        // execute query
        $this->query($sql);

        return $this->_conn->affected_rows;
    }
    
    public function updateWhere($table, $parameters, $what, $wValue) {                                    
        $sql = 'update '
        . $table
        . ' set ';

        // build column value pairs
        foreach ($parameters as $key => $value) {
                $sql .= $key . ' = \'' . $value . '\'';

                if($key != end(array_keys($parameters))){
                        $sql .= ', ';
                }
        }

        $sql .= ' where '
        . $what . ' = \'' . $wValue . '\'';

        // execute query
        $this->query($sql);

        return $this->_conn->affected_rows;
    }
       
    public function deleteById($table, $idName, $idValue) {                                   
        $sql = 'delete from '
        . $table
        . ' where '
        . $idName . ' = ' . $idValue;
        
        // execute query
        $this->query($sql);

        return $this->_conn->affected_rows;
    }
}
?>