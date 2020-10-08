<!doctype html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>EasyStore Logistics Sample App</title>

	<link rel="stylesheet" href="https://s3-ap-southeast-1.amazonaws.com/assets.easystore.co/css/uikit.css">
	<script src="https://s3-ap-southeast-1.amazonaws.com/assets.easystore.co/js/uikit.js"></script>
    <script type="text/javascript">
		EasyStoreApp.init({
			apiKey: '<?php echo env('EASYSTORE_CLIENT_ID'); ?>'
		});
	</script>
</head>
<body>
    <div class="page-layout">
        <div class="layout layout--1">

            <div class="layout__section">
                <div class="card">
                    <div class="card__section">
                        <header class="section-header">
                            <div class="header-thumbnail">
                            </div>
                            <div class="header-action">
                                <h3 class="card-subtitle">EasyStore Logistics Sample App</h3>
                                <p>Sample App to explain and simplify the onboarding process for Logistics Partners.</p>
                            </div>
                        </header>
                    </div>
                </div>
            </div>

            <div class="layout__section">
                <div class="card">

                    <div class="card__section">
                        <h3 class="alert bg-info">
                            <strong>Please take note:</strong>
                        </h3>
                        <div class="col-sm-12">
                            <p>
                            The app is developed in PHP as an example and guidance for
                            logistics partners to integrate with EasyStore easily.
                            </p>
                        </div>
                    </div>
                    <hr>

                    <div class="card__section">
                        <h3 class="card-title">Sample Settings Details</h3>

                        <div class="form-group form-group--2">
                            <div class="input-wrapper">
                                <label for="input-label" class="input-label">Account Information</label>
                                <input type="text" class="input-control" id="account" name="account" placeholder="Sample Account Information">
                            </div>
                        </div>

                        <div class="form-group form-group--2">
                            <div class="input-wrapper">
                                <label for="input-label" class="input-label">Account Secret 1</label>
                                <input type="text" class="input-control" id="secret1" name="secret1" placeholder="Sample Account Secret 1">
                            </div>

                            <div class="input-wrapper">
                                <label for="input-label" class="input-label">Account Secret 2</label>
                                <input type="text" class="input-control" id="secret2" name="secret2" placeholder="Sample Account Secret 2">
                            </div>
                        </div>
                    </div>
                    <hr>

                    <div class="card__section">
                        <h3 class="card-title">Additional Notes</h3>
                        <div class="col-sm-12">
                            <ul>
                                <li class="size-22">These are only sample input fields, please adjust based on your own app design and needs.</li>
                                <li class="size-22">The app also did not include integration with database.</li>
                                <li class="size-22">Database integration shall be completed by logistics partners to store relevant merchant details.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card__section">
                        <div class="text-right" id="submit_button">
                            <button type="submit" id="save" name="save" class="btn btn-primary" value="save">Sample Save Settings Button</button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</body>
</html>
