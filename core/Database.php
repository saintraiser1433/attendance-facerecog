<?php

namespace fingerprint;

use mysqli;

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

class Database {
    private const host = "localhost";
    private const user = "root";
    private const password = "";
    private const database = "testdb";
    private $connection;

    function __construct(){
        $this->connection = new mysqli(self::host, self::user, self::password, self::database);
        if (mysqli_connect_errno()) {
            printf("Connection Failed: %s\n",  mysqli_connect_errno());
            exit();
        }
        
        // Set MySQL session timezone to match PHP timezone (Asia/Manila = UTC+8)
        $this->connection->query("SET time_zone = '+08:00'");
    }

    function getUserInfo($name): array {
        $sql_query = "SELECT * FROM voters WHERE name=?";
        $param_type = "s";
        $param_array = [$name];
        $result = $this->select($sql_query, $param_type, $param_array);
        return $result;
    }

    function select($sql_query, $sql_param_type="", $param_array=array()){
        if ($statement = $this->connection->prepare($sql_query)){
            if (!empty($sql_param_type) && !empty($param_array)){
                $this->bindQueryParams($statement, $sql_param_type, $param_array);
            }

            $statement->execute();
            $sql_query_result = $statement->get_result();

            if ($sql_query_result->num_rows > 0){
                while ($row = $sql_query_result->fetch_assoc()){
                    $result_set[] = $row;
                }
            }

            if (!empty($result_set)){
                return $result_set;
            }
        }
    }

    public function insert($sql_query, $sql_param_type = "", $param_array = array())
    {
        if ($statement = $this->connection->prepare($sql_query)) {
            $this->bindQueryParams($statement, $sql_param_type, $param_array);
            $statement->execute();
            $insert_id = $statement->insert_id;
            $statement->close();
            return $insert_id;
        } else {
            // Don't echo errors - it breaks JSON responses
            // Log error instead of outputting
            error_log("Database insert error: " . $this->connection->error . " | Query: " . $sql_query);
            return -1; // Return a value that indicates an error occurred
        }
    }

    function execute(){
        //pass
    }

    function update($sql_query, $sql_param_type="", $param_array=array()){
        if ($statement = $this->connection->prepare($sql_query)){
            if (!empty($sql_param_type) and !(empty($param_array))){
                $this->bindQueryParams($statement, $sql_param_type, $param_array);
                $statement->execute();
                $statement->store_result();
                return $statement->affected_rows;
            }
        }
        return 0;
    }

    function bindQueryParams($statement, $sql_param_type, $param_array=array()){
        $param_value_reference[] = & $sql_param_type;
        for ($i = 0; $i < count($param_array); $i++){
            $param_value_reference[] = & $param_array[$i];
        }

        call_user_func_array(array($statement, 'bind_param'), $param_value_reference);
    }
}
