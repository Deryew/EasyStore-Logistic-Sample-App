<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sample Logistics App Fulfillment Success</title>

  <link rel="stylesheet" href="https://s3-ap-southeast-1.amazonaws.com/assets.easystore.co/css/uikit.css">
  <script src="https://s3-ap-southeast-1.amazonaws.com/assets.easystore.co/js/uikit.js"></script>

</head>

<body>
  <header class="page-header-wrapper">
    <div class="page-header">
      <div class="page-header__main text-center">
        <h1 class="page-header__title">Sample Logistics App</h1>
        <p>May include description.</p>
      </div>
    </div>
  </header>
  <div class="page-layout">
    <div class="layout layout--1">
      <div class="layout__section">
        <div class="card">
          <div class="card__section">

          <div class="card__section">
            <h2 class="card-title">Fulfillment Information</h2>

            <div class="shipping-info-wrapper">
              <div class="shipping-info">
                <h3 class="card-subtitle">Order Number</h3>
                <p name="order_number">{{$order_number}}</p>
              </div>

            </div>

            @if(!empty($tracking_number))
            <div class="shipping-info-wrapper">
              <div class="shipping-info">
                <h3 class="card-subtitle">Tracking URL</h3>
                <a href="{{$tracking_url}}">{{$tracking_number}}</a>
              </div>
            </div>
            @endif

            {{--May include shipping info--}}
            @if($shipping_address)
            <div class="shipping-info-wrapper">

              <div class="shipping-info">
                <h3 class="card-subtitle">Shipping Address</h3>
                <p>{{$shipping_address['province']}}{{$shipping_address['city']}}
                <br>{{$shipping_address['address1']}} <br>@if(!empty($shipping_address['address2'])){{$shipping_address['address2']}}@endif</p>
              </div>

            </div>
            @endif
          </div>

          <div class="card__section">
            @if(!empty($airwaybill_url))
            <div class="text-left">
              <a class="btn btn-primary" href="{{$airwaybill_url}}">Download AWB</a>
            </div>
            @endif
            <div class="text-right">
              @if($status != 'success')
                <a href="{{ url()->previous() }}" style="margin-right: 20px;">Back</a>
              @endif
              <a class="btn btn-primary" href="{{$back_to_order}}">Back to Order</a>
            </div>
          </div>

        </div>

      </div>
    </div>
  </div>
</body>

<script>
</script>

</html>
