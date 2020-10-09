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

            <form action="/fulfillment/create" method="post" class="layout__section">
                <div class="card">
                    <div class="card__section">
                        <header class="section-header section-header--wrap">
                            <div class="header-thumbnail">
                                <!-- Add an icon for your logistic service -->
                                <!-- <img src="test.png"> -->
                            </div>
                            <div class="header-action">
                                <h3 class="card_subtitle">Sample Logistics App Fulfillment</h3>
                                <p>Fill in this section with a description of your company or logistic services provided.</p>
                                <p>This is a sample UI for your create fulfillment page.</p>
                            </div>
                        </header>
                    </div>
                </div>

                <div class="card">
                    <div class="card__section">
                        <h3 class="card-subtitle">Order Information</h3>
                        <br>
                        <p>Order Number: {{$order_number}}</p>
                        <br>

                        <div class="form-group form-group--2">
                            <div class="input-wrapper">
                                <label>Amount</label>
                                <div class="input-group">
                                    <!-- Can be any currency -->
                                    <span class="input-group-addon">MYR</span>
                                    <input class="input-control" type="number" value="{{$total_amount}}" disabled/>
                                </div>
                            </div>
                        </div>

                        <!-- List Product -->
                        <div class="table-scroll">
                            <table class="table-list table-fulfillment">
                                <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-weight">Weight(g)</th>
                                    <th class="text-quantity">Quantity</th>
                                    <th class="text-amount">Price(Currency)</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($order_item as $item)
                                <tr>
                                    <td>{{str_limit($item['product_name'], $limit = 30, $end = '...')}}</td>
                                    <td>{{$item['grams']}}</td>
                                    <td>{{$item['quantity']}}</td>
                                    <td>{{$item['price']}}</td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Other sections    -->
                        <div class="input-wrapper">
                            <label for="list-label">Things to take note: </label>
                            <ol>
                                <li>This is a sample only includes order details in this page.</li>
                                <li>Please modify the content 'data' variable needed to suit your needs.</li>
                                <li>Other common sections includes information of sender and receiver.</li>
                            </ol>
                        </div>

                    </div>
                </div>

                <div class="card__section">
                    <div class="text-right">
                        <a class="btn-large btn" onclick="goBack()">Back</a>
                        <button type="submit" id="fulfill" name="fulfill" class="btn btn-primary btn-large" value="Fulfill">Fulfill</button>
                    </div>
                </div>

            </form>

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
