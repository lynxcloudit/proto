<?php
require_once('vendor/autoload.php');

use setasign\Fpdi\Fpdi;


require '*******';
require_once '*******';
require '../rpl.php';
require_once('../tcpdf/tcpdf.php');
require_once('../tcpdf/tcpdf_barcodes_2d.php');


if(!isset($_POST['submv']))
{
    die("Nessun documento inviato.");
}

if($_POST['subj'] == "")
{
    die("Specificare un oggetto.");
}

// initiate FPDI
$did = rpl::guidv4();
$time = time();

$date = date("d_m_Y", $time);

$base = "*******/proto/";


$temp = explode(".", $_FILES['upfile']["name"]);
$newfilename = strtoupper(uniqid()) . '.' . end($temp);
if(!move_uploaded_file($_FILES['upfile']['tmp_name'], "./".$newfilename))
{
    rpl::log($uid, "[ERROR]", "cdn/upload", "upload file non riuscito: ".$_FILES['upfile']['name']);
    http_response_code(415);
    die();
}

$hashOrig = hash_file('sha256', $newfilename);
            $mysqli = new mysqli(********);
            $query = $mysqli->prepare("SELECT id FROM protocount ORDER BY id DESC LIMIT 1");
            $query->execute();         
               $result = mysqli_stmt_get_result($query);
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $lastid = $row['id']+1;
                $protonr = str_pad($lastid, 6, '0', STR_PAD_LEFT);
            $query->close();      

$uniqid = strtoupper(uniqid());
ob_start();


$barcodeobj = new TCPDF2DBarcode('https://*******/'.$uniqid."", 'QRCODE,H');
$protobc = $barcodeobj->getBarcodeSVGcode(2, 2, 'black');

$htmlt = '<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->


    
<html>
    <head>
    <style>
    @media print {
  @page { margin: 0; }
  body { margin: 1.6cm; }
  }
  @font-face {
    font-family: \'uniform\';
    src: url(\'https://********/fonts/uniform/uniform-webfont.woff2\') format(\'woff2\'),
         url(\'https://********/fonts/uniform/uniform-webfont.woff\') format(\'woff\');
    font-weight: normal;
    font-style: normal;

}

@font-face {
font-family: \'calibril\';
font-style: normal;
font-weight: normal;
src: local(\'Calibri Light\'), url(\'https://********/fonts/calibrilight/calibril.woff\') format(\'woff\');
}

</style>
        <meta charset="UTF-8">
        <title></title>
    </head>
     <body>
        <div style="padding-top: 100px; display:flex; font-family:uniform; font-size: 0.5em;">
            <div>'.$protobc.'</div> 
            <div style="padding-left: 10px;">'.rpl::getOrgNames(2).' ('.rpl::getOrgInfo()[0]['description'][8].')
                <br>Prot. N&deg; '.$protonr.'
                <br>Uscita '.date("d/m/Y H:i:s T", $time).'
                <br>Checksum origine (SHA-256): 
                <br>'.$hashOrig.'
                <br>Emesso da: '.strtoupper($ou).' ('.$uid.')
            </div> 
        </div>  
     </body>
</html>';


file_put_contents($did.".bc.htm", $htmlt);

exec("chromium --headless --disable-gpu --print-to-pdf=".$did.".bc.pdf https://********/proto/".$did.".bc.htm --no-sandbox --no-margins");

fclose($did.".bc.htm");
unlink($did.".bc.htm") or die("Couldn't delete file");

$pdf = new Fpdi();
$pdf->setSourceFile($did.'.bc.pdf'); 
$backId = $pdf->importPage(1);

$pageCount = $pdf->setSourceFile($newfilename);

    $pdf->AddPage();
    // add the background
    $pdf->useTemplate($backId);
    // import the content page
    $tplId = $pdf->importPage(1);
    // add it
    $pdf->useTemplate($tplId);

if($pageCount > 1) {
    for($i = 2; $i <= $pageCount; $i++) {
    $pdf->AddPage();
    $tplId = $pdf->importPage($i);
    $pdf->useTemplate($tplId);
    }
}
//see the results
//$out = $pdf->Output();  
//$pdf->Output($did.".pdf",'F');
$out = $pdf->Output('', 'S');
file_put_contents($did.".pdf", $out);

switch($_POST['tipov'])
{
    case "oggetto" :
        $tipo = "verbale";
        $fid = 11;        
        break;
    case "resoconto" :
        $tipo = "resoconto";
        $fid = 5;
        break;
    case "coord" :
        if(!rpl::isCoord($uid)){ die("Non autorizzato."); }
        $tipo = "Coordinamento";
        $fid = 15;        
        break;
    case "deliberazione" :
        if(!rpl::isCoord($uid)){ die("Non autorizzato."); }
        $tipo = "Deliberazione";
        $fid = 22;        
        break; 
    case "atti" :
        if(!rpl::isCoord($uid)){ die("Non autorizzato."); }
        $tipo = "Atto";
        $fid = 20;        
        break;  
    case "cpd" :
   //     if(!rpl::isCoord($uid)){ die("Non autorizzato."); }
        $tipo = "vr_cpd";
        $fid = 12;        
        break;          
    default :
     //   http_response_code(408);
      //  die();
        break;
}

$com = strtoupper($tipo)." ".strtolower(strtok($_POST['subj'], " "))." del ".date("d/m/Y", $time);
$kwd = $tipo."; ".strtolower(strtok($_POST['subj'], " "));

 

$nome = $date."_".strtoupper($tipo)."_". strtolower(strtok($_POST['subj'], " "));

$cmd = "/********seeddms-adddoc --config /*********/conf/settings.xml -n ".$nome." -k '".$kwd."' -c '".$com."' -u ******** -F ".$fid." -f /*********/proto/".$did.".pdf";
exec($cmd);

$data = "";
$data = file_get_contents($did.".pdf");
$hashEnd = hash_file('sha256', $did.".pdf");

            $guid = rpl::getGuid($uid);
            $mysqli = new mysqli(**********);
            $query = $mysqli->prepare("SELECT * FROM `tblDocuments` WHERE owner = **** AND name = ? AND comment = ? AND folder = ?");
            $query->bind_param('sss',$nome,$com,$fid);
            $query->execute();
            $result = mysqli_stmt_get_result($query);
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $iddms = $row['id'];

            $dmsurl = "https://*********/op/op.ViewOnline.php?documentid=".$iddms."&version=1";
            
            $mysqli = new mysqli(***********);
            $query = $mysqli->prepare("INSERT INTO `metadati`(`did`, `uniqid`, `autore`, `ts_rilascio`, `tipo_doc`, `subj`,`dms_lk`) VALUES (?,?,?,?,?,?,?)");
            $query->bind_param('sssisss',$did,$uniqid,rpl::getGuid($uid),$time,$tipo,$_POST['subj'],$dmsurl);
            $query->execute();         
            $query->close();
            
            $mysqli = new mysqli(***********);
            $query = $mysqli->prepare("INSERT INTO `protocount`(`did`, `csum_before`, `csum_after`) VALUES (?,?,?)");
            $query->bind_param('sss',$did,$hashOrig,$hashEnd);
            $query->execute();         
            $query->close();            

fclose($did.".pdf");
unlink($did.".pdf") or die("Couldn't delete file");
header("Content-type: application/octet-stream");
header("Content-disposition: attachment;filename=".$did.".pdf");



if($data == "")
{
    $type = "[ERROR]";
    $svc = "portal/verbgen";
    $act = "Il documento ".$did." non è stato generato.";    
}
else
{
    $type = "[INFO]";
    $svc = "portal/verbgen";
    $act = "Documento ".$did." generato e salvato correttamente.";    
}
rpl::log($uid, $type, $svc, $act);
echo $data;


//INSERT INTO `protocount`(`id`, `did`) VALUES ([value-1],[value-2])