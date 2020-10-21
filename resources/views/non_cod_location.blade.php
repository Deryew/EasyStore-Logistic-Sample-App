<html>
<head>
    <title>Test App Pickup</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js" type="text/javascript"></script>
  <link rel="stylesheet" href="https://s3-ap-southeast-1.amazonaws.com/assets.easystore.co/css/uikit.css">
</head>

<body>
    <div class="content-wrapper">
        <div class="card mt-md-4">
            <div class="card-body">
                <h3>Test App Pickup Location</h3>

                <p>This is the part where the map or something similar should be placed for
                customers to select their pickup location.
                </p>
            </div>
            <br>
            <a href="{{ url()->previous() }}" style="margin-right: 20px;">Back</a>
            <button type="button" class="btn btn-primary" onClick="get_location();">Select</button>
        </div>
    </div>
    <script type="text/javascript">

        var xhr = $.ajax();

        console.log(window.location.hostname);

        function get_location(){
            xhr = $.ajax({
                type: "post",
                async: true,
                url: "https://"+window.location.hostname+"/apps/easystore/pickup-rate",
                dataType: "json",
                data: {
                    // your data
                    test: "test"
                },
                beforeSend: function(d){
                    xhr.abort();
                },
                success: function(d){
                    console.log(d);
                    if (d.rate || d.rate >= 0 ) {
                        setCookie("_app_pickup", d.cookie_params, 365);
                    } else {
                        if (d.error) {
                            var obj = { status: "error", message: "Unable to pickup."};
                            var myJSON = JSON.stringify(obj);

                            var encoded = window.btoa(myJSON)
                            setCookie("_app_pickup", encoded, 365);
                        }
                    }
                    window.location.href="/checkout/index";
                },
                fail: function(d){
                    console.log(d);
                }
            });
        }

        function setCookie(cname, cvalue, exdays) {
			var d = new Date();
			d.setTime(d.getTime() + (exdays*24*60*60*1000));
			var expires = "expires="+ d.toUTCString();
			document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
		}

    </script>
</body>
</html>
