<x-sisp::layouts.app>
	<div class="sisp-fullpage-container">
		<div class="sisp-fullpage-card success">
			<div class="sisp-icon-wrapper">
				<x-sisp::icons.check-circle class="sisp-icon success-icon"/>
			</div>
			<h1 class="sisp-title success-text">{{ __('Purchase Completed') }}</h1>
			<p class="sisp-description">
				{{ __('Dear customer, thank you for completing your transaction. We appreciate your trust in our services. If you have any questions or require assistance, please do not hesitate to contact our support team. Have a great day!') }}
			</p>
			<div class="sisp-button-wrapper">
				<x-sisp::redirect-button :name="__('Ok')"/>
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
          animation: fadeInPage 0.6s ease-out forwards;
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
          animation: fadeInUp 0.8s ease-out forwards;
      }

      .dark .sisp-fullpage-card {
          background-color: #18181b;
          color: #f9f9f9;
      }

      .sisp-icon-wrapper {
          display: flex;
          justify-content: center;
          margin-bottom: 1.2rem;
          animation: pulse 1.5s infinite;
      }

      .sisp-icon {
          width: 60px;
          height: 60px;
      }

      .success-icon {
          color: #22c55e;
      }

      .sisp-title {
          font-size: 1.75rem;
          font-weight: 800;
          margin-top: 0.5rem;
          animation: fadeIn 0.9s ease-out;
      }

      .success-text {
          color: #16a34a;
      }

      .sisp-description {
          margin-top: 1rem;
          font-size: 1rem;
          line-height: 1.5;
          animation: fadeIn 1.1s ease-out;
      }

      .sisp-button-wrapper {
          margin-top: 2rem;
          animation: fadeIn 1.3s ease-out;
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