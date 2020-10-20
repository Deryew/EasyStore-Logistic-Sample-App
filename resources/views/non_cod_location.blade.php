<html>
<head>
    <title>Test App Pickup</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js" type="text/javascript"></script>
    <link rel="stylesheet" href="https://s3-ap-southeast-1.amazonaws.com/assets.easystore.co/css/uikit.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
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
                url: "https://testapp-easystore.herokuapp.com/easystore/pickup-rate",
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
