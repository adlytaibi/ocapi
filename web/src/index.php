<?php
/*
* NetApp OnCommand API Services
*
* Listing Clusters, Nodes, Aggregates, Volumes and Lifs
*
* PHP version 7
*
* @author Adly Taibi
* @version 1.0
*/
ob_start();
if(session_status()!=PHP_SESSION_ACTIVE) session_start();
include 'utils.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>NetApp <?php print $title; ?></title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel='shortcut icon' sizes='16x16 24x24 32x32 40x40 48x48 64x64 96x96 128x128 192x192' href='favicon.ico'>
  <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ" crossorigin="anonymous" type="text/javascript"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous" type="text/javascript"></script>
  <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js" integrity="sha384-r3v0/sXe5ocDydKBFcxP390rex2dEm9qN3Yv68S6uNX/F3b/RtMdGMUADZ8tabkz" crossorigin="anonymous" type="text/javascript"></script>
  <link href="https://cdn.datatables.net/1.10.18/css/jquery.dataTables.min.css" integrity="sha384-1UXhfqyOyO+W+XsGhiIFwwD3hsaHRz2XDGMle3b8bXPH5+cMsXVShDoHA3AH/y/p" crossorigin="anonymous" rel="stylesheet">
  <link href="https://cdn.datatables.net/plug-ins/1.10.18/integration/font-awesome/dataTables.fontAwesome.css" integrity="sha384-R23Y5Ln/v2u8Fnm8wgUfyorZ0F/SCkgq3ofU7ysWZZmBprH/COcICDtAWpqMxniT" crossorigin="anonymous" rel="stylesheet">
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous" rel="stylesheet" id="bootstrap-css">
  <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous" rel="stylesheet">
  <link rel='stylesheet' href='css/mestyle.css'>
  <style type='text/css' class='init'></style>
  <script type='text/javascript' class='init'>
    $(document).ready(function() {
      $('#smtable').DataTable();
    } );
  </script>
</head>
<body>
<div class=container>
<a href="." class="btn btn-primary btn-sm" role="button"><i class="fa fa-home" style="font-size:18px;"></i></a>
<?php
if ($_POST) {
  postdata();
}
endpointform();
print $melist;
print $msg;
?>
</div>
</body>
</html>
