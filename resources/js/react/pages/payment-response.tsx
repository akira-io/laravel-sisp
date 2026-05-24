import { AnimatedBackground } from '@/components/landing/animated-background';
import SmoothScroll from '@/components/landing/smooth-scroll';
import { useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { AlertCircle, Check, CheckCircle2, ChevronRight, Clock, Copy, Download, FileDown, Home, RefreshCw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { DEFAULT_TRANSLATIONS, type ErrorData, type InvoiceData, type TransactionData, type Translations } from './payment-response-data';
interface PaymentResponseProps {
    transaction: TransactionData;
    error?: ErrorData | null;
    translations?: Translations;
    allowRetry?: boolean;
    retryUrl?: string | null;
    invoice?: InvoiceData | null;
    payload: Record<string, unknown>;
}

export default function PaymentResponse({
    transaction,
    error,
    translations = DEFAULT_TRANSLATIONS,
    allowRetry = true,
    retryUrl = null,
    invoice,
}: PaymentResponseProps) {
    const isSuccess = transaction.status === 'completed';
    const isFailed = transaction.status === 'failed';
    const isPending = transaction.status === 'pending';
    const t = translations.payment;

    const { post, processing } = useForm({});

    const [showLeaveConfirm, setShowLeaveConfirm] = useState(false);
    const [copiedReference, setCopiedReference] = useState(false);
    const isBackButtonRef = useRef(false);
    const beforeUnloadHandlerRef = useRef<((e: BeforeUnloadEvent) => void) | null>(null);

    const handleRetryPayment = () => {
        if (retryUrl) {
            post(retryUrl);
        }
    };

    const handleCopyReference = async () => {
        if (!transaction.merchant_ref) return;

        try {
            await navigator.clipboard.writeText(transaction.merchant_ref);
            setCopiedReference(true);
            setTimeout(() => setCopiedReference(false), 2000);
        } catch {}
    };

    const handleLeaveConfirm = () => {
        if (beforeUnloadHandlerRef.current) {
            window.removeEventListener('beforeunload', beforeUnloadHandlerRef.current);
            beforeUnloadHandlerRef.current = null;
        }

        if (isBackButtonRef.current) {
            isBackButtonRef.current = false;
            window.history.back();
        } else {
            window.location.href = '/';
        }
    };

    useEffect(() => {
        if (isFailed) return;

        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            e.preventDefault();
            e.returnValue = '';
            return '';
        };

        beforeUnloadHandlerRef.current = handleBeforeUnload;
        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            beforeUnloadHandlerRef.current = null;
        };
    }, [isFailed]);

    useEffect(() => {
        if (isFailed) return;

        window.history.pushState(null, '', window.location.href);

        const handlePopState = () => {
            window.history.pushState(null, '', window.location.href);
            isBackButtonRef.current = true;
            setShowLeaveConfirm(true);
        };

        window.addEventListener('popstate', handlePopState);

        return () => {
            window.removeEventListener('popstate', handlePopState);
        };
    }, [isFailed]);

    const getStatusConfig = () => {
        if (isSuccess) {
            return {
                icon: CheckCircle2,
                iconBg: 'bg-gradient-to-br from-green-600 to-green-500 shadow-green-500/20',
                titleColor: 'text-green-600 dark:text-green-500',
                cardBorder: 'border-green-200 dark:border-green-800',
                cardBg: 'bg-green-50 dark:bg-green-950/50',
                statusBadge: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
            };
        }
        if (isFailed) {
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
    };

    const config = getStatusConfig();
    const StatusIcon = config.icon;

    const getTitle = () => {
        if (isSuccess) return t.success_title;
        if (isFailed) return t.failed_title;
        return t.pending_title;
    };

    const getMessage = () => {
        if (isSuccess) return t.success_message;
        if (isFailed) return t.failed_message;
        return t.pending_message;
    };

    const getStatusText = () => {
        if (isSuccess) return t.success_status;
        if (isFailed) return t.failed_status;
        return t.pending_status;
    };

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
                            <div className={`flex h-20 w-20 items-center justify-center rounded-2xl shadow-xl ${config.iconBg}`}>
                                <StatusIcon className={`h-10 w-10 text-white ${isPending ? 'animate-pulse' : ''}`} />
                            </div>
                        </div>
                        <h1 className={`mb-3 text-3xl font-black tracking-tight md:text-4xl ${config.titleColor}`}>{getTitle()}</h1>
                        <p className="text-zinc-600 dark:text-zinc-400">{getMessage()}</p>
                    </motion.div>

                    {/* Transaction Details Card */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.2 }}
                        className={`mb-6 overflow-hidden rounded-2xl border ${config.cardBorder} ${config.cardBg} shadow-lg backdrop-blur-sm`}
                    >
                        <div className="p-6">
                            <div className="space-y-4">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-sm font-medium text-zinc-600 dark:text-zinc-400">{t.reference}</span>
                                    <div className="flex items-center gap-2">
                                        <span className="font-mono font-bold text-zinc-900 dark:text-white">
                                            {transaction.merchant_ref || t.declined}
                                        </span>
                                        {transaction.merchant_ref && (
                                            <button
                                                onClick={handleCopyReference}
                                                className="group relative m-0 flex h-8 shrink-0 items-center justify-center rounded-lg transition-all"
                                                title={t.copy_reference}
                                            >
                                                {copiedReference ? (
                                                    <Check className="h-4 w-4 text-green-600 dark:text-green-500" />
                                                ) : (
                                                    <Copy className="h-3.5 w-3.5 text-zinc-400 transition-colors group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300" />
                                                )}
                                            </button>
                                        )}
                                    </div>
                                </div>

                                {(isSuccess || isPending) && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium text-zinc-600 dark:text-zinc-400">{t.amount}</span>
                                        <span className="text-lg font-bold text-zinc-900 dark:text-white">{transaction.formatted_amount}</span>
                                    </div>
                                )}

                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium text-zinc-600 dark:text-zinc-400">{t.status}</span>
                                    <span className={`rounded-full px-3 py-1 text-sm font-bold ${config.statusBadge}`}>{getStatusText()}</span>
                                </div>

                                {isFailed && error && (
                                    <>
                                        <div className="my-4 border-t border-zinc-200 dark:border-zinc-700" />
                                        <div className="space-y-3">
                                            <div>
                                                <span className="text-xs font-medium tracking-wider text-zinc-500 uppercase">{t.category}</span>
                                                <p className="font-medium text-zinc-900 dark:text-white">{error.categoryLabel}</p>
                                            </div>
                                            <div>
                                                <span className="text-xs font-medium tracking-wider text-zinc-500 uppercase">{t.reason}</span>
                                                <p className="font-medium text-zinc-900 dark:text-white">{error.label}</p>
                                            </div>
                                            <div>
                                                <span className="text-xs font-medium tracking-wider text-zinc-500 uppercase">{t.action}</span>
                                                <p className="font-medium text-zinc-900 dark:text-white">{error.actionLabel}</p>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </motion.div>

                    {/* Invoice Download */}
                    {isSuccess && invoice?.pdf_url && (
                        <>
                            {/* Download Invoice Alert */}
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.3 }}
                                className="mb-4 overflow-hidden rounded-2xl border border-purple-200 bg-purple-50/80 shadow-lg backdrop-blur-sm dark:border-purple-800 dark:bg-purple-950/50"
                            >
                                <div className="p-4">
                                    <div className="flex gap-3">
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-600 shadow-lg shadow-purple-500/20">
                                            <FileDown className="h-5 w-5 text-white" />
                                        </div>
                                        <div>
                                            <h3 className="font-bold text-purple-800 dark:text-purple-300">{t.download_invoice_alert_title}</h3>
                                            <p className="mt-1 text-sm text-purple-700 dark:text-purple-400">{t.download_invoice_alert_message}</p>
                                        </div>
                                    </div>
                                </div>
                            </motion.div>

                            {/* Download Button */}
                            <motion.a
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.35 }}
                                href={invoice.pdf_url}
                                target="_blank"
                                download={`${invoice.invoice_number}.pdf`}
                                className="mb-4 flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-purple-600 to-purple-500 font-bold text-white shadow-xl shadow-purple-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]"
                            >
                                <Download className="h-5 w-5" />
                                {t.invoice_download}
                            </motion.a>
                        </>
                    )}

                    {/* Pending Note */}
                    {isPending && (
                        <motion.p
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ delay: 0.3 }}
                            className="mb-6 text-center text-sm text-zinc-500 dark:text-zinc-400"
                        >
                            {t.pending_note}
                        </motion.p>
                    )}

                    {/* Action Buttons */}
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 }} className="space-y-3">
                        {isFailed && allowRetry && retryUrl && (
                            <button
                                onClick={handleRetryPayment}
                                disabled={processing}
                                className="flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-purple-600 to-purple-500 text-lg font-bold text-white shadow-xl shadow-purple-500/20 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processing ? (
                                    <RefreshCw className="h-5 w-5 animate-spin" />
                                ) : (
                                    <>
                                        <RefreshCw className="h-5 w-5" />
                                        {t.retry_payment}
                                    </>
                                )}
                            </button>
                        )}

                        {(isSuccess || isPending) && (
                            <button
                                type="button"
                                onClick={(e) => {
                                    e.preventDefault();
                                    setShowLeaveConfirm(true);
                                }}
                                className={`flex h-14 w-full items-center justify-center gap-2 rounded-2xl text-lg font-bold transition-all hover:scale-[1.02] active:scale-[0.98] ${
                                    isPending
                                        ? 'bg-linear-to-r from-yellow-600 to-yellow-500 text-white shadow-xl shadow-yellow-500/20'
                                        : 'border-2 border-zinc-200 bg-white text-zinc-700 shadow-lg hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-700'
                                }`}
                            >
                                <Home className="h-5 w-5" />
                                {t.back_home}
                                <ChevronRight className="h-5 w-5" />
                            </button>
                        )}

                        {isFailed && (
                            <a
                                href="/"
                                className="flex h-14 w-full items-center justify-center gap-2 rounded-2xl border-2 border-zinc-200 bg-white font-bold text-zinc-700 shadow-lg transition-all hover:scale-[1.02] hover:border-zinc-300 hover:bg-zinc-50 active:scale-[0.98] dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-700"
                            >
                                <Home className="h-5 w-5" />
                                {t.cancel_payment}
                            </a>
                        )}
                    </motion.div>
                </div>

                {/* Leave Confirmation Dialog */}
                {showLeaveConfirm && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
                        <motion.div
                            initial={{ opacity: 0, scale: 0.95 }}
                            animate={{ opacity: 1, scale: 1 }}
                            className="relative mx-4 w-full max-w-sm overflow-hidden rounded-3xl bg-zinc-900 shadow-2xl"
                        >
                            {/* Close button */}
                            <button
                                onClick={() => setShowLeaveConfirm(false)}
                                className="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-zinc-700 text-zinc-300 transition-colors hover:bg-zinc-600"
                            >
                                ✕
                            </button>

                            <div className="px-6 pt-8 pb-6 text-center">
                                {/* Icon */}
                                <div className="mb-5 flex justify-center">
                                    <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-600 shadow-lg shadow-purple-500/30">
                                        <AlertCircle className="h-8 w-8 text-white" />
                                    </div>
                                </div>

                                <h3 className="mb-3 text-2xl font-black text-white">{t.leave_confirmation_title}</h3>
                                <p className="text-sm leading-relaxed text-zinc-400">{t.leave_confirmation_message}</p>
                            </div>

                            <div className="border-t border-zinc-700" />

                            <div className="flex items-center gap-3 p-4">
                                <button
                                    onClick={() => setShowLeaveConfirm(false)}
                                    className="flex-1 py-3 text-sm font-semibold text-zinc-400 transition-colors hover:text-zinc-200"
                                >
                                    {t.stay_on_page}
                                </button>
                                <button
                                    onClick={handleLeaveConfirm}
                                    className="flex-1 rounded-full bg-purple-600 py-3 text-sm font-bold text-white transition-all hover:bg-purple-500 active:scale-[0.98]"
                                >
                                    {t.leave_page}
                                </button>
                            </div>
                        </motion.div>
                    </div>
                )}
            </motion.div>
        </SmoothScroll>
    );
}
