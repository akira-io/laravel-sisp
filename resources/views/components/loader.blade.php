<div class='flex items-center justify-center flex-col' role="status">
	<!-- Loader -->
	<div {{ $attributes->class(['w-16 h-16 border-8 border-t-8 border-slate-800 border-t-violet-500 rounded-full animate-spin mb-4']) }}>
	</div>

	<!-- Animated Loading Text -->
	<p class="dark:text-white text-lg font-semibold animate-pulse"> {{ __('Loading...') }} </p>
</div>
