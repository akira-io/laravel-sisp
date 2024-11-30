<x-sisp::layouts.app>
	<body class="min-h-screen flex justify-center items-center bg-gray-900" onload="document.forms[0].submit()" >

	<div class="flex items-center justify-center min-h-dvh">
		<x-sisp::loader/>
		<form action="{{ $url }}" method="post">
			@csrf
			@foreach ($fields as $key => $value)
				<input type="hidden" name="{{ $key }}" value="{{ $value }}">
			@endforeach
		</form>
	</div>

	</>
</x-sisp::layouts.app>
