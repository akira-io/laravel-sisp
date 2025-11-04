<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-lg">
      <!-- Success State -->
      <div v-if="isSuccess" class="space-y-6">
        <div class="space-y-2 text-center">
          <div class="flex justify-center">
            <div class="rounded-full bg-green-100 p-4">
              <svg class="h-8 w-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
          <h2 class="text-2xl font-bold text-green-600">Pagamento Realizado com Sucesso!</h2>
          <p class="text-gray-600">Sua transação foi processada com sucesso.</p>
        </div>

        <div class="space-y-2 rounded-lg bg-green-50 p-4 text-sm text-gray-700">
          <p><strong>ID da Transação:</strong> {{ transaction.id }}</p>
          <p><strong>Referência:</strong> {{ transaction.merchant_ref }}</p>
          <p><strong>Valor:</strong> {{ transaction.amount }} {{ transaction.currency }}</p>
          <p><strong>Status:</strong> <span class="font-medium text-green-600">Completado</span></p>
        </div>
      </div>

      <!-- Failed State -->
      <div v-else-if="isFailed" class="space-y-6">
        <div class="space-y-2 text-center">
          <div class="flex justify-center">
            <div class="rounded-full bg-red-100 p-4">
              <svg class="h-8 w-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
          <h2 class="text-2xl font-bold text-red-600">Pagamento Recusado</h2>
          <p class="text-gray-600">Desculpe, seu pagamento não foi processado.</p>
        </div>

        <div class="space-y-2 rounded-lg bg-red-50 p-4 text-sm text-gray-700">
          <p><strong>ID da Transação:</strong> {{ transaction.id }}</p>
          <p><strong>Motivo:</strong> {{ transaction.message_type || 'Recusado' }}</p>
          <p><strong>Status:</strong> <span class="font-medium text-red-600">Falhou</span></p>
        </div>
      </div>

      <!-- Pending State -->
      <div v-else class="space-y-6">
        <div class="space-y-2 text-center">
          <div class="flex justify-center">
            <div class="rounded-full bg-yellow-100 p-4">
              <svg class="h-8 w-8 animate-spin text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <h2 class="text-2xl font-bold text-yellow-600">Pagamento Pendente</h2>
          <p class="text-gray-600">Seu pagamento está sendo processado.</p>
        </div>

        <div class="space-y-2 rounded-lg bg-yellow-50 p-4 text-sm text-gray-700">
          <p><strong>ID da Transação:</strong> {{ transaction.id }}</p>
          <p><strong>Referência:</strong> {{ transaction.merchant_ref }}</p>
          <p><strong>Status:</strong> <span class="font-medium text-yellow-600">Pendente</span></p>
        </div>

        <p class="text-center text-xs text-gray-500">
          Você receberá uma confirmação em breve. Por favor, não feche esta página.
        </p>
      </div>

      <div class="mt-8">
        <a href="/" class="block w-full rounded-lg bg-blue-600 px-4 py-2 text-center font-medium text-white transition hover:bg-blue-700">
          Voltar ao Início
        </a>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface TransactionData {
  id: number;
  status: 'completed' | 'failed' | 'pending';
  amount: number;
  currency: string;
  merchant_ref: string;
  merchant_session: string;
  message_type?: string;
}

interface PaymentResponseProps {
  transaction: TransactionData;
  payload: Record<string, any>;
}

const props = defineProps<PaymentResponseProps>();

const isSuccess = computed(() => props.transaction.status === 'completed');
const isFailed = computed(() => props.transaction.status === 'failed');
</script>