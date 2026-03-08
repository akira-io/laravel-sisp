<template>
    <SmoothScroll>
        <AnimatedBackground />

        <div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <!-- Header with Icon -->
                <div class="mb-8 text-center fade-in-up" style="animation-delay: 0.1s">
                    <div class="mb-6 flex justify-center">
                        <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-purple-600 shadow-xl shadow-purple-500/20 dark:bg-purple-500">
                            <CreditCard class="h-10 w-10 text-white" />
                        </div>
                    </div>
                    <h1 class="mb-3 text-3xl font-black tracking-tight text-zinc-900 md:text-4xl dark:text-white">{{ t.redirect_title }}</h1>
                    <p class="text-zinc-600 dark:text-zinc-400">{{ t.redirect_description }}</p>
                </div>

                <!-- Security Info Card -->
                <div class="mb-6 overflow-hidden rounded-2xl border border-green-200 bg-green-50/80 shadow-lg backdrop-blur-sm dark:border-green-800 dark:bg-green-950/50 fade-in-up" style="animation-delay: 0.2s">
                    <div class="p-6">
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-600 shadow-lg shadow-green-500/20">
                                <CheckCircle2 class="h-5 w-5 text-white" />
                            </div>
                            <span class="text-lg font-bold text-green-700 dark:text-green-400">{{ t.secure_transaction }}</span>
                        </div>

                        <div class="space-y-3">
                            <div v-for="(feature, index) in securityFeatures" :key="index" class="flex items-center gap-3 fade-in-left" :style="`animation-delay: ${0.3 + index * 0.1}s`">
                                <component :is="feature.icon" class="h-4 w-4 text-green-600 dark:text-green-500" />
                                <span class="text-sm text-green-700 dark:text-green-300">{{ feature.text }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Countdown / Status Card -->
                <div
                    class="overflow-hidden rounded-2xl border shadow-lg backdrop-blur-sm fade-in-up"
                    :class="countdown > 0
                        ? 'border-purple-200 bg-purple-50/80 dark:border-purple-800 dark:bg-purple-950/50'
                        : 'border-green-200 bg-green-50/80 dark:border-green-800 dark:bg-green-950/50'"
                    style="animation-delay: 0.4s"
                >
                    <div class="p-6">
                        <div class="flex items-center justify-center gap-3">
                            <template v-if="countdown > 0">
                                <Loader2 class="h-6 w-6 animate-spin text-purple-600 dark:text-purple-500" />
                                <span class="text-lg font-bold text-purple-700 dark:text-purple-400">{{ countdownText }}</span>
                            </template>
                            <template v-else>
                                <Loader2 class="h-6 w-6 animate-spin text-green-600 dark:text-green-500" />
                                <span class="text-lg font-bold text-green-700 dark:text-green-400">{{ t.connecting }}</span>
                            </template>
                        </div>

                        <!-- Progress bar -->
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="countdown > 0 ? 'bg-purple-600 dark:bg-purple-500' : 'bg-gradient-to-r from-green-600 to-green-500'"
                                :style="`width: ${countdown > 0 ? ((2 - countdown) / 2) * 100 : 100}%`"
                            />
                        </div>
                    </div>
                </div>

                <!-- Branding -->
                <div class="mt-8 text-center fade-in" style="animation-delay: 0.5s">
                    <p class="text-xs text-zinc-500 dark:text-zinc-500">{{ t.developed_by }}</p>
                </div>
            </div>

            <!-- Hidden form -->
            <form ref="formRef" :action="endpoint" method="post" class="hidden">
                <input v-for="(value, key) in fields" :key="key" type="hidden" :name="key" :value="String(value)" />
            </form>
        </div>
    </SmoothScroll>
</template>

<script setup lang="ts">
import AnimatedBackground from '@/components/landing/AnimatedBackground.vue';
import SmoothScroll from '@/components/landing/SmoothScroll.vue';
import { CheckCircle2, CreditCard, Loader2, Lock, Shield, ShieldCheck } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface PaymentFormProps {
    endpoint: string;
    fields: Record<string, string | number>;
    translations?: {
        redirect_title: string;
        redirect_description: string;
        secure_transaction: string;
        official_portal: string;
        ssl_encryption: string;
        data_protected: string;
        redirecting_in: string;
        connecting: string;
        developed_by: string;
    };
}

const props = defineProps<PaymentFormProps>();

const t = computed(() => props.translations ?? {
    redirect_title: 'A redirecionar para o SISP',
    redirect_description: 'Você será redirecionado para o portal de pagamento seguro',
    secure_transaction: 'Transação Segura',
    official_portal: 'Portal oficial do Sistema Bancário de Cabo Verde',
    ssl_encryption: 'Encriptação SSL de nível bancário',
    data_protected: 'Os seus dados estão protegidos',
    redirecting_in: 'A redirecionar em :count segundo|A redirecionar em :count segundos',
    connecting: 'A ligar ao SISP...',
    developed_by: 'Desenvolvido por: Kidiatoliny Gonçalves',
});

const securityFeatures = computed(() => [
    { icon: ShieldCheck, text: t.value.official_portal },
    { icon: Lock, text: t.value.ssl_encryption },
    { icon: Shield, text: t.value.data_protected },
]);

const formRef = ref<HTMLFormElement | null>(null);
const countdown = ref(2);
const hasSubmitted = ref(false);

const countdownText = computed(() => {
    const parts = t.value.redirecting_in.split('|');
    const text = countdown.value === 1 ? parts[0] : (parts[1] ?? parts[0]);
    return text.replace(':count', countdown.value.toString());
});

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

<style scoped>
.fade-in-up {
    animation: fadeInUp 0.5s ease both;
}

.fade-in-left {
    animation: fadeInLeft 0.5s ease both;
}

.fade-in {
    animation: fadeIn 0.5s ease both;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInLeft {
    from { opacity: 0; transform: translateX(-10px); }
    to   { opacity: 1; transform: translateX(0); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
</style>
