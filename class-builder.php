<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="utf-8"/>
	<meta content="width=device-width, initial-scale=1, maximum-scale=1" name="viewport">
	<title>Class Builder by flavios</title>
	<link href="css/seu-stylesheet.css" rel="stylesheet"/>
	<script src="https://code.jquery.com/jquery-1.11.3.js"></script>
    
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.min.css">
</head>
<body>
<form class="form-horizontal">
<fieldset>

<!-- Form Name -->
<legend>Class Builder</legend>

<!-- Text input-->
<div class="form-group">
    <label class="col-md-4 control-label" for="textinput">Nome da classe</label>  
  <div class="col-md-4">
  <input id="textinput" name="txtnomeclass" placeholder="Nome da classe" class="form-control input-md" type="text">

  </div>
</div>

<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="textinput">Campo</label>  
  <div class="col-md-4">
  <input id="textinput" name="txtField[]" placeholder="nome campo" class="form-control input-md" type="text">
  </div>
 
 
  <div class="col-md-2">
  <input id="textinput" name="txtType[]" placeholder="Tipo" class="form-control input-md" type="text">
  </div>
  <a  id="btnadd" name="singlebutton" class="btn btn-primary">+</a>

</div>
   <div  id="conteiner_campos">
      </div>
      
<!-- Button -->
<div class="form-group">
  <label class="col-md-4 control-label" for="singlebutton"></label>
  <div class="col-md-4">
    <button  class="btn btn-primary">Gerar</button>
  </div>
</div>

</fieldset>
</form>

<script type="text/javascript">
$(document).ready(function(){
    $("#btnadd").click(function(){
    $( "#conteiner_campos" ).append( '<div class="form-group">  <label class="col-md-4 control-label" for="textinput">Campo</label>    <div class="col-md-4">  <input id="textinput" name="txtField[]" placeholder="nome campo" class="form-control input-md" type="text">  </div>  <div class="col-md-2">  <input id="textinput" name="txtType[]" placeholder="Tipo" class="form-control input-md" type="text">  </div> </div>' );
});
    
    });
</script>
<?php 

//print_r($_REQUEST);
if(isset($_REQUEST['txtField']))
{
$buf = "";
    $buf .= "/******************************************************************************\n";
    $buf .= "* Class for " . $_REQUEST['txtnomeclass'] . "\n";
    $buf .= "*******************************************************************************/\n\n";
    $buf .= "class ".$_REQUEST['txtnomeclass']."\n{\n";
    
       foreach($_REQUEST['txtField'] as $column_name)
       {
           $buf .= "\tprivate \$$column_name;\n\n"; 
       }
      $buf .= "\tpublic function __construct(\$$column_name='')\n";
      $buf .= "\t{\n";

      $buf .= "\t}\n\n";
      
    foreach($_REQUEST['txtField'] as $column_name)
    {
      $column_name = str_replace('-','_',$column_name);
      $column_name = str_replace('.','_',$column_name);
      
      $buf .= "\tpublic function set$column_name(\$$column_name='')\n";
      $buf .= "\t{\n";
 
      $buf .= "\t\t\$this->$column_name = \$$column_name;\n";
      $buf .= "\t\treturn true;\n";

      $buf .= "\t}\n\n";

      $buf .= "\tpublic function get$column_name()\n";
      $buf .= "\t{\n";
      $buf .= "\t\treturn \$this->$column_name;\n";
      $buf .= "\t}\n\n";
    }
    $buf .= "}\n";
echo "<textarea style='width:60%;height:300px;' class='col-md-4'>".$buf."</textarea>";
}
?>
</body>
</html>

