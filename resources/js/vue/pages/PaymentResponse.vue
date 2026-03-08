<template>
    <SmoothScroll>
        <AnimatedBackground />

        <div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <!-- Header with Icon -->
                <div class="mb-8 text-center fade-in-up" style="animation-delay: 0.1s">
                    <div class="mb-6 flex justify-center">
                        <div :class="`flex h-20 w-20 items-center justify-center rounded-2xl shadow-xl ${statusConfig.iconBg}`">
                            <component :is="statusConfig.icon" :class="`h-10 w-10 text-white ${isPending ? 'animate-pulse' : ''}`" />
                        </div>
                    </div>
                    <h1 :class="`mb-3 text-3xl font-black tracking-tight md:text-4xl ${statusConfig.titleColor}`">{{ title }}</h1>
                    <p class="text-zinc-600 dark:text-zinc-400">{{ message }}</p>
                </div>

                <!-- Transaction Details Card -->
                <div :class="`mb-6 overflow-hidden rounded-2xl border ${statusConfig.cardBorder} ${statusConfig.cardBg} shadow-lg backdrop-blur-sm fade-in-up`" style="animation-delay: 0.2s">
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ t.reference }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-zinc-900 dark:text-white">
                                        {{ transaction.merchant_ref || t.declined }}
                                    </span>
                                    <button
                                        v-if="transaction.merchant_ref"
                                        @click="copyReference"
                                        class="group relative m-0 flex h-8 shrink-0 items-center justify-center rounded-lg transition-all"
                                        :title="t.copy_reference"
                                    >
                                        <Check v-if="copiedReference" class="h-4 w-4 text-green-600 dark:text-green-500" />
                                        <Copy v-else class="h-3.5 w-3.5 text-zinc-400 transition-colors group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300" />
                                    </button>
                                </div>
                            </div>

                            <div v-if="isSuccess || isPending" class="flex items-center justify-between">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ t.amount }}</span>
                                <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ transaction.formatted_amount }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ t.status }}</span>
                                <span :class="`rounded-full px-3 py-1 text-sm font-bold ${statusConfig.statusBadge}`">{{ statusText }}</span>
                            </div>

                            <template v-if="isFailed && error">
                                <div class="my-4 border-t border-zinc-200 dark:border-zinc-700" />
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-xs font-medium tracking-wider text-zinc-500 uppercase">{{ t.category }}</span>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ error.categoryLabel }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs font-medium tracking-wider text-zinc-500 uppercase">{{ t.reason }}</span>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ error.label }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs font-medium tracking-wider text-zinc-500 uppercase">{{ t.action }}</span>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ error.actionLabel }}</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Invoice Download -->
                <template v-if="isSuccess && invoice?.pdf_url">
                    <!-- Alert -->
                    <div class="mb-4 overflow-hidden rounded-2xl border border-purple-200 bg-purple-50/80 shadow-lg backdrop-blur-sm dark:border-purple-800 dark:bg-purple-950/50 fade-in-up" style="animation-delay: 0.3s">
                        <div class="p-4">
                            <div class="flex gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-600 shadow-lg shadow-purple-500/20">
                                    <FileDown class="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-purple-800 dark:text-purple-300">{{ t.download_invoice_alert_title }}</h3>
                                    <p class="mt-1 text-sm text-purple-700 dark:text-purple-400">{{ t.download_invoice_alert_message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Download Button -->
                    <a
                        :href="invoice.pdf_url"
                        target="_blank"
                        :download="`${invoice.invoice_number}.pdf`"
                        class="mb-4 flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-purple-600 to-purple-500 font-bold text-white shadow-xl shadow-purple-500/20 transition-all hover:scale-[1.02] active:scale-[0.98] fade-in-up"
                        style="animation-delay: 0.35s"
                    >
                        <Download class="h-5 w-5" />
                        {{ t.invoice_download }}
                    </a>
                </template>

                <!-- Pending Note -->
                <p v-if="isPending" class="mb-6 text-center text-sm text-zinc-500 dark:text-zinc-400 fade-in" style="animation-delay: 0.3s">
                    {{ t.pending_note }}
                </p>

                <!-- Action Buttons -->
                <div class="space-y-3 fade-in-up" style="animation-delay: 0.4s">
                    <button
                        v-if="isFailed && allowRetry"
                        @click="retryPayment"
                        :disabled="processing"
                        class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-purple-600 to-purple-500 text-lg font-bold text-white shadow-xl shadow-purple-500/20 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <RefreshCw :class="`h-5 w-5 ${processing ? 'animate-spin' : ''}`" />
                        <span v-if="!processing">{{ t.retry_payment }}</span>
                    </button>

                    <button
                        v-if="isSuccess || isPending"
                        type="button"
                        @click="showLeaveConfirm = true"
                        :class="`flex h-14 w-full items-center justify-center gap-2 rounded-2xl text-lg font-bold transition-all hover:scale-[1.02] active:scale-[0.98] ${
                            isPending
                                ? 'bg-linear-to-r from-yellow-600 to-yellow-500 text-white shadow-xl shadow-yellow-500/20'
                                : 'border-2 border-zinc-200 bg-white text-zinc-700 shadow-lg hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-700'
                        }`"
                    >
                        <Home class="h-5 w-5" />
                        {{ t.back_home }}
                        <ChevronRight class="h-5 w-5" />
                    </button>

                    <a
                        v-if="isFailed"
                        href="/"
                        class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl border-2 border-zinc-200 bg-white font-bold text-zinc-700 shadow-lg transition-all hover:scale-[1.02] hover:border-zinc-300 hover:bg-zinc-50 active:scale-[0.98] dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-700"
                    >
                        <Home class="h-5 w-5" />
                        {{ t.cancel_payment }}
                    </a>
                </div>
            </div>

            <!-- Leave Confirmation Dialog -->
            <Transition name="fade">
                <div v-if="showLeaveConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
                    <div class="relative mx-4 w-full max-w-sm overflow-hidden rounded-3xl bg-zinc-900 shadow-2xl scale-in">
                        <!-- Close button -->
                        <button
                            @click="showLeaveConfirm = false"
                            class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-zinc-700 text-zinc-300 transition-colors hover:bg-zinc-600"
                        >
                            ✕
                        </button>

                        <div class="px-6 pt-8 pb-6 text-center">
                            <div class="mb-5 flex justify-center">
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-600 shadow-lg shadow-purple-500/30">
                                    <AlertCircle class="h-8 w-8 text-white" />
                                </div>
                            </div>
                            <h3 class="mb-3 text-2xl font-black text-white">{{ t.leave_confirmation_title }}</h3>
                            <p class="text-sm leading-relaxed text-zinc-400">{{ t.leave_confirmation_message }}</p>
                        </div>

                        <div class="border-t border-zinc-700" />

                        <div class="flex items-center gap-3 p-4">
                            <button
                                @click="showLeaveConfirm = false"
                                class="flex-1 py-3 text-sm font-semibold text-zinc-400 transition-colors hover:text-zinc-200"
                            >
                                {{ t.stay_on_page }}
                            </button>
                            <button
                                @click="confirmLeave"
                                class="flex-1 rounded-full bg-purple-600 py-3 text-sm font-bold text-white transition-all hover:bg-purple-500 active:scale-[0.98]"
                            >
                                {{ t.leave_page }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </SmoothScroll>
</template>

<script setup lang="ts">
import AnimatedBackground from '@/components/landing/AnimatedBackground.vue';
import SmoothScroll from '@/components/landing/SmoothScroll.vue';
import { useForm } from '@inertiajs/vue3';
import { AlertCircle, Check, CheckCircle2, ChevronRight, Clock, Copy, Download, FileDown, Home, RefreshCw } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface TransactionData {
    id: number;
    status: 'completed' | 'failed' | 'pending';
    amount: number;
    formatted_amount: string;
    currency: string;
    merchant_ref: string;
    merchant_session: string;
    message_type?: string;
}

interface ErrorData {
    code: string;
    label: string;
    category: string;
    categoryLabel: string;
    action: string;
    actionLabel: string;
}

interface InvoiceData {
    invoice_number: string;
    pdf_url: string;
}

interface Translations {
    payment: {
        success_title: string;
        success_message: string;
        success_status: string;
        failed_title: string;
        failed_message: string;
        failed_status: string;
        pending_title: string;
        pending_message: string;
        pending_status: string;
        pending_note: string;
        reference: string;
        amount: string;
        status: string;
        category: string;
        reason: string;
        action: string;
        invoice_download: string;
        back_home: string;
        declined: string;
        retry_payment: string;
        cancel_payment: string;
        copy_reference: string;
        download_invoice_alert_title: string;
        download_invoice_alert_message: string;
        leave_confirmation_title: string;
        leave_confirmation_message: string;
        leave_page: string;
        stay_on_page: string;
    };
}

interface PaymentResponseProps {
    transaction: TransactionData;
    error?: ErrorData | null;
    translations?: Translations;
    allowRetry?: boolean;
    invoice?: InvoiceData | null;
    payload: Record<string, unknown>;
}

const props = withDefaults(defineProps<PaymentResponseProps>(), {
    allowRetry: true,
    error: null,
    invoice: null,
});

const isSuccess = computed(() => props.transaction.status === 'completed');
const isFailed = computed(() => props.transaction.status === 'failed');
const isPending = computed(() => props.transaction.status === 'pending');

const t = computed(() => props.translations?.payment ?? {
    success_title: 'Pagamento Concluído com Sucesso!',
    success_message: 'A sua transação foi processada com sucesso.',
    success_status: 'Concluído',
    failed_title: 'Pagamento Recusado',
    failed_message: 'Desculpe, o seu pagamento não foi processado.',
    failed_status: 'Falhou',
    pending_title: 'Pagamento Pendente',
    pending_message: 'O seu pagamento está a ser processado.',
    pending_status: 'Pendente',
    pending_note: 'Receberá uma confirmação em breve. Por favor, não feche esta página.',
    reference: 'Referência',
    amount: 'Montante',
    status: 'Estado',
    category: 'Categoria',
    reason: 'Motivo',
    action: 'Ação',
    invoice_download: 'Baixar Fatura',
    back_home: 'Voltar ao Início',
    declined: 'Recusado',
    retry_payment: 'Tentar Novamente',
    cancel_payment: 'Cancelar',
    copy_reference: 'Copiar referência',
    download_invoice_alert_title: 'Faça o download da sua fatura',
    download_invoice_alert_message: 'Guarde uma cópia da sua fatura para os seus registos.',
    leave_confirmation_title: 'Tem a certeza que quer sair?',
    leave_confirmation_message: 'Se sair agora, poderá perder informações sobre o seu pagamento.',
    leave_page: 'Sair da página',
    stay_on_page: 'Ficar na página',
});

const statusConfig = computed(() => {
    if (isSuccess.value) {
        return {
            icon: CheckCircle2,
            iconBg: 'bg-gradient-to-br from-green-600 to-green-500 shadow-green-500/20',
            titleColor: 'text-green-600 dark:text-green-500',
            cardBorder: 'border-green-200 dark:border-green-800',
            cardBg: 'bg-green-50 dark:bg-green-950/50',
            statusBadge: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        };
    }
    if (isFailed.value) {
        return {
            icon: AlertCircle,
            iconBg: 'bg-red-600 dark:bg-red-500 shadow-red-500/20',
            titleColor: 'text-red-600 dark:text-red-500',
            cardBorder: 'border-red-200 dark:border-red-800',
            cardBg: 'bg-red-50 dark:bg-red-950/50',
            statusBadge: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
        };
    }
    return {
        icon: Clock,
        iconBg: 'bg-gradient-to-br from-yellow-600 to-yellow-500 shadow-yellow-500/20',
        titleColor: 'text-yellow-600 dark:text-yellow-500',
        cardBorder: 'border-yellow-200 dark:border-yellow-800',
        cardBg: 'bg-yellow-50 dark:bg-yellow-950/50',
        statusBadge: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400',
    };
});

const title = computed(() => {
    if (isSuccess.value) return t.value.success_title;
    if (isFailed.value) return t.value.failed_title;
    return t.value.pending_title;
});

const message = computed(() => {
    if (isSuccess.value) return t.value.success_message;
    if (isFailed.value) return t.value.failed_message;
    return t.value.pending_message;
});

const statusText = computed(() => {
    if (isSuccess.value) return t.value.success_status;
    if (isFailed.value) return t.value.failed_status;
    return t.value.pending_status;
});

const { post, processing } = useForm({ transaction_id: props.transaction.id });

const showLeaveConfirm = ref(false);
const copiedReference = ref(false);
const isBackButton = ref(false);
let beforeUnloadHandler: ((e: BeforeUnloadEvent) => void) | null = null;

const retryPayment = () => post('/sisp/retry-payment');

const copyReference = async () => {
    if (!props.transaction.merchant_ref) return;
    try {
        await navigator.clipboard.writeText(props.transaction.merchant_ref);
        copiedReference.value = true;
        setTimeout(() => (copiedReference.value = false), 2000);
    } catch {
        // silently fail
    }
};

const confirmLeave = () => {
    if (beforeUnloadHandler) {
        window.removeEventListener('beforeunload', beforeUnloadHandler);
        beforeUnloadHandler = null;
    }
    if (isBackButton.value) {
        isBackButton.value = false;
        window.history.back();
    } else {
        window.location.href = '/';
    }
};

onMounted(() => {
    if (isFailed.value) return;

    beforeUnloadHandler = (e: BeforeUnloadEvent) => {
        e.preventDefault();
        e.returnValue = '';
    };
    window.addEventListener('beforeunload', beforeUnloadHandler);

    window.history.pushState(null, '', window.location.href);
    const handlePopState = () => {
        window.history.pushState(null, '', window.location.href);
        isBackButton.value = true;
        showLeaveConfirm.value = true;
    };
    window.addEventListener('popstate', handlePopState);

    onUnmounted(() => {
        if (beforeUnloadHandler) window.removeEventListener('beforeunload', beforeUnloadHandler);
        window.removeEventListener('popstate', handlePopState);
    });
});
</script>

<style scoped>
.fade-in-up {
    animation: fadeInUp 0.5s ease both;
}
.fade-in {
    animation: fadeIn 0.5s ease both;
}
.scale-in {
    animation: scaleIn 0.2s ease both;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.95); }
    to   { opacity: 1; transform: scale(1); }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
