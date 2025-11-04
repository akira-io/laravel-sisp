<template>
  <div class="flex min-h-screen items-center justify-center bg-background">
    <div class="w-full max-w-md rounded-lg bg-card p-8 shadow-lg border border-border">
      <div class="space-y-6 text-center">
        <!-- Header -->
        <div class="space-y-2">
          <div class="flex justify-center">
            <div class="rounded-full bg-primary p-4">
              <svg class="h-8 w-8 text-primary-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                <path d="M22 11.429V18a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-1.5m17-5.071V10a2 2 0 0 0-2-2h-1m3 3.429h-3" />
                <path d="M19 8v6.5a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2zm0 0H5.5" />
              </svg>
            </div>
          </div>
          <h2 class="text-xl font-semibold text-foreground">Redirecionando para SISP</h2>
          <p class="text-sm text-muted-foreground">Você será redirecionado para o portal seguro de pagamentos</p>
        </div>

        <!-- Security Info -->
        <div class="rounded-lg bg-muted p-4 text-left border border-border">
          <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600 dark:text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div class="text-sm">
              <p class="mb-1 font-medium text-foreground">Transação Segura</p>
              <ul class="space-y-1 text-xs text-muted-foreground">
                <li>• Portal oficial do Sistema Bancário de Cabo Verde</li>
                <li>• Criptografia SSL de nível bancário</li>
                <li>• Seus dados estão protegidos</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Countdown -->
        <div v-if="countdown > 0" class="space-y-4">
          <div class="rounded-lg bg-blue-50 dark:bg-blue-950 p-4 border border-blue-200 dark:border-blue-800">
            <p class="text-sm font-medium text-blue-700 dark:text-blue-300">
              Redirecionando em {{ countdown }} segundo{{ countdown !== 1 ? 's' : '' }}...
            </p>
          </div>
        </div>
        <div v-else class="space-y-4">
          <div class="rounded-lg bg-green-50 dark:bg-green-950 p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-700 dark:text-green-300">Conectando ao SISP...</p>
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