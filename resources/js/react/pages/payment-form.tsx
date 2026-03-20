import { AnimatedBackground } from '@/components/landing/animated-background';
import SmoothScroll from '@/components/landing/smooth-scroll';
import { motion } from 'framer-motion';
import { CheckCircle2, CreditCard, Loader2, Lock, Shield, ShieldCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

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

export default function PaymentForm({ endpoint, fields, translations }: PaymentFormProps) {
    const t = translations || {
        redirect_title: 'A redirecionar para o SISP',
        redirect_description: 'Você será redirecionado para o portal de pagamento seguro',
        secure_transaction: 'Transação Segura',
        official_portal: 'Portal oficial do Sistema Bancário de Cabo Verde',
        ssl_encryption: 'Encriptação SSL de nível bancário',
        data_protected: 'Os seus dados estão protegidos',
        redirecting_in: 'A redirecionar em :count segundo|A redirecionar em :count segundos',
        connecting: 'A ligar ao SISP...',
        developed_by: 'Desenvolvido por: Kidiatoliny Goncalves',
    };
    const formRef = useRef<HTMLFormElement>(null);
    const [countdown, setCountdown] = useState(2);
    const [hasSubmitted, setHasSubmitted] = useState(false);

    useEffect(() => {
        const timer = setInterval(() => {
            setCountdown((prev) => {
                if (prev <= 1) {
                    clearInterval(timer);
                    if (formRef.current && !hasSubmitted) {
                        setHasSubmitted(true);
                        formRef.current.submit();
                    }
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);

        return () => clearInterval(timer);
    }, [hasSubmitted]);

    const getCountdownText = () => {
        const parts = t.redirecting_in.split('|');
        const text = countdown === 1 ? parts[0] : parts[1] || parts[0];
        return text.replace(':count', countdown.toString());
    };

    const securityFeatures = [
        { icon: ShieldCheck, text: t.official_portal },
        { icon: Lock, text: t.ssl_encryption },
        { icon: Shield, text: t.data_protected },
    ];

    return (
        <SmoothScroll>
            <AnimatedBackground />

            <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ duration: 0.5 }}
                className="relative flex min-h-screen flex-col items-center justify-center px-4 py-12"
            >
                <div className="w-full max-w-md">
                    {/* Header with Icon */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.1 }}
                        className="mb-8 text-center"
                    >
                        <div className="mb-6 flex justify-center">
                            <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-purple-600 shadow-xl shadow-purple-500/20 dark:bg-purple-500">
                                <CreditCard className="h-10 w-10 text-white" />
                            </div>
                        </div>
                        <h1 className="mb-3 text-3xl font-black tracking-tight text-zinc-900 md:text-4xl dark:text-white">{t.redirect_title}</h1>
                        <p className="text-zinc-600 dark:text-zinc-400">{t.redirect_description}</p>
                    </motion.div>

                    {/* Security Info Card */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.2 }}
                        className="mb-6 overflow-hidden rounded-2xl border border-green-200 bg-green-50/80 shadow-lg backdrop-blur-sm dark:border-green-800 dark:bg-green-950/50"
                    >
                        <div className="p-6">
                            <div className="mb-4 flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-green-600 shadow-lg shadow-green-500/20">
                                    <CheckCircle2 className="h-5 w-5 text-white" />
                                </div>
                                <span className="text-lg font-bold text-green-700 dark:text-green-400">{t.secure_transaction}</span>
                            </div>

                            <div className="space-y-3">
                                {securityFeatures.map((feature, index) => (
                                    <motion.div
                                        key={index}
                                        initial={{ opacity: 0, x: -10 }}
                                        animate={{ opacity: 1, x: 0 }}
                                        transition={{ delay: 0.3 + index * 0.1 }}
                                        className="flex items-center gap-3"
                                    >
                                        <feature.icon className="h-4 w-4 text-green-600 dark:text-green-500" />
                                        <span className="text-sm text-green-700 dark:text-green-300">{feature.text}</span>
                                    </motion.div>
                                ))}
                            </div>
                        </div>
                    </motion.div>

                    {/* Countdown / Status Card */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.4 }}
                        className={`overflow-hidden rounded-2xl border shadow-lg backdrop-blur-sm ${
                            countdown > 0
                                ? 'border-purple-200 bg-purple-50/80 dark:border-purple-800 dark:bg-purple-950/50'
                                : 'border-green-200 bg-green-50/80 dark:border-green-800 dark:bg-green-950/50'
                        }`}
                    >
                        <div className="p-6">
                            <div className="flex items-center justify-center gap-3">
                                {countdown > 0 ? (
                                    <>
                                        <div className="relative">
                                            <Loader2 className="h-6 w-6 animate-spin text-purple-600 dark:text-purple-500" />
                                        </div>
                                        <span className="text-lg font-bold text-purple-700 dark:text-purple-400">{getCountdownText()}</span>
                                    </>
                                ) : (
                                    <>
                                        <Loader2 className="h-6 w-6 animate-spin text-green-600 dark:text-green-500" />
                                        <span className="text-lg font-bold text-green-700 dark:text-green-400">{t.connecting}</span>
                                    </>
                                )}
                            </div>

                            {/* Progress bar */}
                            <div className="mt-4 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <motion.div
                                    initial={{ width: '0%' }}
                                    animate={{ width: countdown > 0 ? `${((2 - countdown) / 2) * 100}%` : '100%' }}
                                    transition={{ duration: 0.5 }}
                                    className={`h-full rounded-full ${
                                        countdown > 0 ? 'bg-purple-600 dark:bg-purple-500' : 'bg-gradient-to-r from-green-600 to-green-500'
                                    }`}
                                />
                            </div>
                        </div>
                    </motion.div>

                    {/* SISP Logo / Branding */}
                    <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ delay: 0.5 }} className="mt-8 text-center">
                        <p className="text-xs text-zinc-500 dark:text-zinc-500">{t.developed_by}</p>
                    </motion.div>
                </div>

                {/* Hidden form */}
                <form ref={formRef} action={endpoint} method="post" className="hidden">
                    {Object.entries(fields).map(([key, value]) => (
                        <input key={key} type="hidden" name={key} value={String(value)} />
                    ))}
                </form>
            </motion.div>
        </SmoothScroll>
    );
}
