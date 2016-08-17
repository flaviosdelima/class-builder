<?php
////////////////////////////////////////////////////
// TableDescriptor class
//
// Accepts table name as input, generates a description
// of each column for the table. next to add is attaching
// comments to the columns.
//
// ClassBuilder class
//
// Supply a table name to generate get/set, load/save/create
// functionality for specified table. Data validation
// may optionally be employed during attribute setting.
//
// Author: Andy Honeycutt
// Email: ahoneycutt@gmail.com
// Date: 2008-08-26
//
// This program is free software; you can redistribute it
// and/or modify it under the terms of the
// GNU General Public License version 2 (GPLv2)
// as published by the Free Software Foundation.
// See COPYING for details.
//
///////////////////////////////////////////////////////

$database_connection_information = "
define(DB_HOST,'127.0.0.1');
define(DB_USER,'myuser');
define(DB_PASS,'mypassword');
define(DB_BASE,'mydatabase');
";

eval($database_connection_information);

class Settings
{
  public static $validator_written = false;
}

class TableDescriptor
{
  private $table;
  private $dblink;
  private $columns=array();
  private $primary_key;

  public function __construct($table)
  {
    $this->table = $table;
    if( $this->Connect() )
      $this->Load();
  }
  public function __destruct()
  {
    if( is_resource($this->dblink) )
      mysql_close($this->dblink);
  }

  public function Connect()
  {
    if( $this->table != '' )
    {
      return $this->getDbLink();
    }
  }

  public function Query($q)
  {
    $result = mysql_query($q,$this->Connect());
    return $result;
  }
  public function GetRow($r)
  {
    return mysql_fetch_assoc($r);
  }

  public function getDbLink()
  {
    if( !is_resource($this->dblink))
    {
      $dblink = mysql_connect(DB_HOST,DB_USER,DB_PASS);
      mysql_select_db(DB_BASE);
      $this->dblink = $dblink;
    }
    return $this->dblink;
  }

  public function AddColumn($column)
  {
    $pattern = "([a-z]{1,})[\(]{0,}([0-9]{0,})[\)]{0,}";
    $matches = array();
    ereg($pattern,$column['Type'],$matches);
    $column['Type']   = $matches[1];
    $column['Length'] = $matches[2];
    $this->columns[] = $column;
    if( $column['Key'] == 'PRI' )
      $this->primary_key = $column['Field'];
  }

  public function getTable() { return $this->table; }
  public function getColumns() { return $this->columns; }
  public function getPrimaryKey() { return $this->primary_key; }

  public function Load()
  {
    $query = "SHOW COLUMNS FROM {$this->getTable()}";
    $result = $this->Query($query);
    while($row = $this->GetRow($result))
      $this->AddColumn($row);
  }
}

class ClassBuilder
{
  private $buffer;
  private $validate;
  private $table_descriptor;
  private $variable_types = array(
    "int"       => "int",
    "text"      => "string",
    "bool"      => "bool",
    "date"      => "int",
    "blob"      => "int",
    "float"     => "int",
    "double"    => "int",
    "bigint"    => "int",
    "tinyint"   => "int",
    "longint"   => "int",
    "varchar"   => "string",
    "smallint"  => "int",
    "datetime"  => "int",
    "timestamp" => "int"
  );

  public function __construct($table='',$validate=false)
  {
    $this->table_descriptor = new TableDescriptor($table);
    $this->validate = $validate;
    $this->Load();
  }

  private function Load()
  {
    $buf = "";
    if( Settings::$validator_written == false )
    {
      $buf .= "class validate\n";
      $buf .= "{\n";
      $buf .= "\tpublic function isstring(\$string)\n";
      $buf .= "\t{\n";
      $buf .= "\t\treturn (is_string(\$string));\n";
      $buf .= "\t}\n\n";

      $buf .= "\tpublic function isint(\$int)\n";
      $buf .= "\t{\n";
      $buf .= "\t\treturn (preg_match(\"/^([0-9.,-]+)$/\", \$int) > 0);\n";
      $buf .= "\t}\n\n";

      $buf .= "\tpublic function isbool(\$bool)\n";
      $buf .= "\t{\n";
      $buf .= "\t\t\$b = 1 * \$bool;\n";
      $buf .= "\t\treturn (\$b == 1 || \$b == 0);\n";
      $buf .= "\t}\n";
      $buf .= "}\n\n";

      Settings::$validator_written = true;
    }

    $buf .= "/******************************************************************************\n";
    $buf .= "* Class for " . DB_BASE . "." . $this->table_descriptor->getTable() . "\n";
    $buf .= "*******************************************************************************/\n\n";
    $buf .= "class {$this->table_descriptor->getTable()}\n{\n";

    foreach($this->table_descriptor->getColumns() as $column)
    {
      $column_name = str_replace('-','_',$column['Field']);
      $buf .= "\t/**\n";
      $buf .= "\t* @var {$this->variable_types[$column['Type']]}\n";
      if( $column['Field'] == $this->table_descriptor->getPrimaryKey() )
      {
        $buf .= "\t* Class Unique ID\n";
      }
      $buf .= "\t*/\n";
      $buf .= "\tprivate \$$column_name;\n\n";
    }

    if( $this->table_descriptor->getPrimaryKey() != '' )
    {
      $pk   = $this->table_descriptor->getPrimaryKey();
      $buf .= "\tpublic function __construct(\$$pk='')\n";
      $buf .= "\t{\n";
      $buf .= "\t\t\$this->set{$pk}(\$$pk);\n";
      $buf .= "\t\t\$this->Load();\n";
      $buf .= "\t}\n\n";

      $buf .= "\tprivate function Load()\n";
      $buf .= "\t{\n";
      $buf .= "\t\t\$dblink = null;\n\n";

      $buf .= "\t\ttry\n";
      $buf .= "\t\t{\n";
      $buf .= "\t\t\t\$dblink = mysql_connect(DB_HOST,DB_USER,DB_PASS);\n";
      $buf .= "\t\t\tmysql_select_db(DB_BASE,\$dblink);\n";
      $buf .= "\t\t}\n";
      $buf .= "\t\tcatch(Exception \$ex)\n";
      $buf .= "\t\t{\n";
      $buf .= "\t\t\techo \"Could not connect to \" . DB_HOST . \":\" . DB_BASE . \"\\n\";\n";
      $buf .= "\t\t\techo \"Error: \" . \$ex->message;\n";
      $buf .= "\t\t\texit;\n";
      $buf .= "\t\t}\n";
      $buf .= "\t\t\$query = \"SELECT * FROM " . $this->table_descriptor->getTable() . " WHERE `$pk`='{\$this->get$pk()}'\";\n\n";
      $buf .= "\t\t\$result = mysql_query(\$query,\$dblink);\n\n";
      $buf .= "\t\twhile(\$row = mysql_fetch_assoc(\$result) )\n";
      $buf .= "\t\t\tforeach(\$row as \$key => \$value)\n";
      $buf .= "\t\t\t{\n";
      $buf .= "\t\t\t\t\$column_name = str_replace('-','_',\$key);\n";
			$buf .= "\t\t\t\t\$this->{\"set\$column_name\"}(\$value);\n\n";
      $buf .= "\t\t\t}\n";
      $buf .= "\t\tif(is_resource(\$dblink)) mysql_close(\$dblink);\n";
      $buf .= "\t}\n\n";

      $update_columns = "";
      foreach($this->table_descriptor->getColumns() as $column)
      {
        if( $column['Field'] != $this->table_descriptor->getPrimaryKey() )
        {
          $column_name = str_replace('-','_',$column['Field']);
          $update_columns .= "\n\t\t\t\t\t\t`{$column['Field']}` = '\" . mysql_real_escape_string(\$this->get$column_name(),\$dblink) . \"',";
        }
      }
      $update_columns = rtrim($update_columns,',');

      $buf .= "\tpublic function Save()\n";
      $buf .= "\t{\n";
      $buf .= "\t\t\$dblink = null;\n\n";

      $buf .= "\t\ttry\n";
      $buf .= "\t\t{\n";
      $buf .= "\t\t\t\$dblink = mysql_connect(DB_HOST,DB_USER,DB_PASS);\n";
      $buf .= "\t\t\tmysql_select_db(DB_BASE,\$dblink);\n";
      $buf .= "\t\t}\n";
      $buf .= "\t\tcatch(Exception \$ex)\n";
      $buf .= "\t\t{\n";
      $buf .= "\t\t\techo \"Could not connect to \" . DB_HOST . \":\" . DB_BASE . \"\\n\";\n";
      $buf .= "\t\t\techo \"Error: \" . \$ex->message;\n";
      $buf .= "\t\t\texit;\n";
      $buf .= "\t\t}\n";
      $buf .= "\t\t\$query = \"UPDATE " . $this->table_descriptor->getTable() . " SET $update_columns \n\t\t\t\t\t\tWHERE `$pk`='{\$this->get$pk()}'\";\n\n";
      $buf .= "\t\tmysql_query(\$query,\$dblink);\n\n";
      $buf .= "\t\tif(is_resource(\$dblink)) mysql_close(\$dblink);\n";
      $buf .= "\t}\n\n";
    }

    $insert_columns = "";
    $insert_values  = "";
    foreach($this->table_descriptor->getColumns() as $column)
    {
      if( $column['Field'] != $this->table_descriptor->getPrimaryKey() )
      {
        $column_name = str_replace('-','_',$column['Field']);
        $insert_columns .= "`{$column['Field']}`,";
        $insert_values  .= "'\" . mysql_real_escape_string(\$this->get$column_name(),\$dblink) . \"',";
      }
    }
    $insert_columns = rtrim($insert_columns,',');
    $insert_values  = rtrim($insert_values,',');

    $buf .= "\tpublic function Create()\n";
    $buf .= "\t{\n";
    $buf .= "\t\t\$dblink = null;\n\n";

    $buf .= "\t\ttry\n";
    $buf .= "\t\t{\n";
    $buf .= "\t\t\t\$dblink = mysql_connect(DB_HOST,DB_USER,DB_PASS);\n";
    $buf .= "\t\t\tmysql_select_db(DB_BASE,\$dblink);\n";
    $buf .= "\t\t}\n";
    $buf .= "\t\tcatch(Exception \$ex)\n";
    $buf .= "\t\t{\n";
    $buf .= "\t\t\techo \"Could not connect to \" . DB_HOST . \":\" . DB_BASE . \"\\n\";\n";
    $buf .= "\t\t\techo \"Error: \" . \$ex->message;\n";
    $buf .= "\t\t\texit;\n";
    $buf .= "\t\t}\n";
    $buf .= "\t\t\$query =\"INSERT INTO {$this->table_descriptor->getTable()} ($insert_columns) VALUES ($insert_values);\";\n";
    $buf .= "\t\tmysql_query(\$query,\$dblink);\n\n";
    $buf .= "\t\tif(is_resource(\$dblink)) mysql_close(\$dblink);\n";
    $buf .= "\t}\n\n";

    foreach($this->table_descriptor->getColumns() as $column)
    {
      $column_name = str_replace('-','_',$column['Field']);
      $buf .= "\tpublic function set$column_name(\$$column_name='')\n";
      $buf .= "\t{\n";
      if( $this->validate )
      {
        $buf .= "\t\tif(validate::is{$this->variable_types[$column['Type']]}(\$$column_name))\n";
        $buf .= "\t\t{\n";
        $buf .= "\t\t\t\$this->$column_name = \$$column_name;\n";
        $buf .= "\t\t\treturn true;\n";
        $buf .= "\t\t}\n";
        $buf .= "\t\treturn false;\n";
      }
      else
      {
        $buf .= "\t\t\$this->$column_name = \$$column_name;\n";
        $buf .= "\t\treturn true;\n";
      }
      $buf .= "\t}\n\n";

      $buf .= "\tpublic function get$column_name()\n";
      $buf .= "\t{\n";
      $buf .= "\t\treturn \$this->$column_name;\n";
      $buf .= "\t}\n\n";
    }

    $buf .= "} // END class {$this->table_descriptor->getTable()}\n\n";
    $this->buffer = $buf;
  }

  public function Get() { return $this->buffer; }
}

/**
 * The following code will grab all of the tables in database (DB_BASE)
 * and create classes for them, as well as spit out print_r statements
 * for debugging.
 */
$dblink = mysql_connect(DB_HOST,DB_USER,DB_PASS);
mysql_select_db(DB_BASE,$dblink);
$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='".DB_BASE."'";
$result = mysql_query($query,$dblink);
$new_classes = array();
echo "<?\n";
echo $database_connection_information."\n";

while($row = mysql_fetch_assoc($result))
{
  $tablename = $row['TABLE_NAME'];
  $new_classes[strtolower($tablename)] = "$tablename";

  $c = new ClassBuilder($tablename,false);
  echo $c->Get();
}

echo "\n";
echo "echo \"<pre>\\n\";\n";

foreach($new_classes as $key=>$value)
{
  echo "\$$key = new $value(1);\n";
  echo "print_r(\$$key);\n\n";
}

echo "echo \"</pre>\\n\";\n";
