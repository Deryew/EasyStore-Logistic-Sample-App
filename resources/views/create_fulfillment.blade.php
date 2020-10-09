<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>Sample Logistics App Create Fulfillment</title>
  <link rel="stylesheet" href="https://s3-ap-southeast-1.amazonaws.com/assets.easystore.co/css/uikit.css">
  <script src="https://s3-ap-southeast-1.amazonaws.com/assets.easystore.co/js/uikit.js"></script>
</head>

<body>
	<div class="page-layout">
		<div class="layout layout--1">


		</div>
	</div>

</body>

<script type="text/javascript">

    function goBack() {
        window.history.back();
    }

    $('form').submit(function() {
        $('#fulfill').addClass("btn-loading");
        $('#fulfill').prop("disabled", true);
    });


</script>


</html>
