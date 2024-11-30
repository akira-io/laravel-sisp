<x-sisp::layouts.app>
			<div class="flex flex-col items-center justify-center mt-10 max-w-sm mx-auto ">
				<div class="p-6 dark:bg-zinc-900 rounded-xl shadow-md">
					<div class='flex items-center justify-center'>
						<x-sisp::icons.exclamation-circle class="size-24 text-red-500"/></div>
					<div class="text-center">
						<h1 class="text-2xl font-bold mt-4 text-red-500">{{__('Purchase has been canceled')}}</h1>
						<p class="mt-2 dark:text-white">{{__('Dear customer, your purchase has been cancelled, for more information do not hesitate to contact us')}}</p>
					<x-sisp::redirect-button :name="__('Ok')"/>
					</div>
				</div>
			</div>
</x-sisp::layouts.app>
