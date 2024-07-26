<!-- resources/views/payment.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Pagamento vinti4</title>
	<style>
      body, html {
          height: 100%;
          margin: 0;
          display: flex;
          justify-content: center;
          align-items: center;
          font-family: Arial, sans-serif;
      }

      .container {
          text-align: center;
      }

      h5 {
          margin-bottom: 20px;
      }

      .loader {
          border: 8px solid #f3f3f3;
          border-top: 8px solid rgb(239, 68, 68);
          border-radius: 50%;
          width: 32px;
          height: 32px;
          animation: spin 2s linear infinite;
          margin-bottom: 20px;
      }

      @keyframes spin {
          0% {
              transform: rotate(0deg);
          }
          100% {
              transform: rotate(360deg);
          }
      }
	</style>
</head>
<body onload="document.forms[0].submit()">
<div class="container">
	<div class="loader"></div>
	<form action="{{ $url }}" method="post">
		@csrf
		{{--		@dd($fields)--}}
		@foreach ($fields as $key => $value)
			<input type="hidden" name="{{ $key }}" value="{{ $value }}">
		@endforeach
	</form>
</div>
</body>
</html>
