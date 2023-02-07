<?php 

class DatabaseClass  
{  
    private $host = "localhost"; // your host name  
    private $username = "root"; // your user name  
    private $password = "root"; // your password  
    private $db = "essentialgrid"; // your database name  
    private $conn;
    protected $prefix = 'esntialwp_';

    protected  function __construct()  
    {  
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db);
        if ($this->conn->connect_errno) {
            throw new Exception("Connect failed: " . $this->conn->connect_error);
        }
    }

    protected function execute($q, $data = false){
        $results = $this->conn->query($q);
        
        if($data){
            if($results->num_rows > 0){
                return  $results->fetch_assoc();
            }else{
                return [];
            }
        }
    }
}  