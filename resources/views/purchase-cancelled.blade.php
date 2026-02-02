<x-sisp::layouts.app>
	<div class="sisp-fullpage-container">
		<div class="sisp-fullpage-card">
			<div class="sisp-icon-wrapper">
				<x-sisp::icons.exclamation-circle class="sisp-icon"/>
			</div>
			<h1 class="sisp-title">{{ __('Purchase has been canceled') }}</h1>
			<p class="sisp-description">
				{{ __('Dear customer, your purchase has been cancelled. For more information, do not hesitate to contact us.') }}
			</p>
			<div class="sisp-button-wrapper">
				<x-sisp::redirect-button variant="neutral" :name="__('Return Home')"/>
			</div>
		</div>
	</div>
	<style>
      .sisp-fullpage-container {
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100vh;
          padding: 1rem;
          background-color: #f9fafb;
      }

      .dark .sisp-fullpage-container {
          background-color: #0f0f0f;
      }

      .sisp-fullpage-card {
          background-color: white;
          color: #1a1a1a;
          border-radius: 1rem;
          box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
          padding: 2.5rem 2rem;
          max-width: 400px;
          width: 100%;
          text-align: center;
      }

      .dark .sisp-fullpage-card {
          background-color: #18181b;
          color: #f9f9f9;
      }

      .sisp-icon-wrapper {
          display: flex;
          justify-content: center;
          margin-bottom: 1.2rem;
      }

      .sisp-icon {
          width: 60px;
          height: 60px;
          color: #ef4444;
      }

      .sisp-title {
          font-size: 1.75rem;
          font-weight: 800;
          color: #dc2626;
          margin-top: 0.5rem;
      }

      .sisp-description {
          margin-top: 1rem;
          font-size: 1rem;
          line-height: 1.5;
      }

      .sisp-button-wrapper {
          margin-top: 2rem;
      }

      @media (prefers-reduced-motion: no-preference) {
          .sisp-fullpage-container {
              animation: fadeInPage 0.6s ease-out forwards;
          }

          .sisp-fullpage-card {
              animation: fadeInUp 0.8s ease-out forwards;
          }

          .sisp-icon-wrapper {
              animation: pulse 1.5s infinite;
          }

          .sisp-title {
              animation: fadeIn 0.9s ease-out;
          }

          .sisp-description {
              animation: fadeIn 1.1s ease-out;
          }

          .sisp-button-wrapper {
              animation: fadeIn 1.3s ease-out;
          }
      }

      @keyframes fadeInPage {
          from {
              opacity: 0;
          }
          to {
              opacity: 1;
          }
      }

      @keyframes fadeInUp {
          from {
              opacity: 0;
              transform: translateY(40px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }

      @keyframes fadeIn {
          from {
              opacity: 0;
          }
          to {
              opacity: 1;
          }
      }

      @keyframes pulse {
          0%, 100% {
              transform: scale(1);
          }
          50% {
              transform: scale(1.1);
          }
      }
	</style>
</x-sisp::layouts.app>