<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-lg">
      <div class="space-y-6 text-center">
        <!-- Header -->
        <div class="space-y-2">
          <div class="flex justify-center">
            <div class="rounded-full bg-blue-600 p-4">
              <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7.548-4.548a.75.75 0 00-1.06 0L12 8.94m0 0a.75.75 0 00-1.06 0M9 12m7.548-4.548L21 7m0 0L12.452 2.452m0 0a.75.75 0 10-1.06 0M21 7v10.5a.75.75 0 01-.75.75H3.75A.75.75 0 013 17.5V7m18 0h-3.5" />
              </svg>
            </div>
          </div>
          <h2 class="text-xl font-semibold text-gray-900">Redirecionando para SISP</h2>
          <p class="text-sm text-gray-600">Você será redirecionado para o portal seguro de pagamentos</p>
        </div>

        <!-- Security Info -->
        <div class="rounded-lg bg-gray-50 p-4 text-left">
          <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div class="text-sm">
              <p class="mb-1 font-medium text-gray-900">Transação Segura</p>
              <ul class="space-y-1 text-xs text-gray-600">
                <li>• Portal oficial do Sistema Bancário de Cabo Verde</li>
                <li>• Criptografia SSL de nível bancário</li>
                <li>• Seus dados estão protegidos</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Countdown -->
        <div v-if="countdown > 0" class="space-y-4">
          <div class="rounded-lg bg-blue-50 p-4">
            <p class="text-sm font-medium text-blue-700">
              Redirecionando em {{ countdown }} segundo{{ countdown !== 1 ? 's' : '' }}...
            </p>
          </div>
        </div>
        <div v-else class="space-y-4">
          <div class="rounded-lg bg-green-50 p-4">
            <p class="text-sm font-medium text-green-700">Conectando ao SISP...</p>
          </div>
        </div>
      </div>

      <form ref="formRef" :action="endpoint" method="post" class="hidden">
        <input
          v-for="(value, key) in fields"
          :key="key"
          type="hidden"
          :name="key"
          :value="String(value)"
        />
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

interface PaymentFormProps {
  endpoint: string;
  fields: Record<string, string | number>;
}

defineProps<PaymentFormProps>();

const formRef = ref<HTMLFormElement | null>(null);
const countdown = ref(3);
const hasSubmitted = ref(false);

onMounted(() => {
  const timer = setInterval(() => {
    countdown.value--;

    if (countdown.value <= 0) {
      clearInterval(timer);
      if (formRef.value && !hasSubmitted.value) {
        hasSubmitted.value = true;
        formRef.value.submit();
      }
    }
  }, 1000);

  onUnmounted(() => clearInterval(timer));
});
</script>