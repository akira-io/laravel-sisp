import { useEffect, useRef, useState } from 'react';

interface PaymentFormProps {
    endpoint: string;
    fields: Record<string, string | number>;
}

export default function PaymentForm({ endpoint, fields }: PaymentFormProps) {
    const formRef = useRef<HTMLFormElement>(null);
    const [countdown, setCountdown] = useState(3);
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

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50">
            <div className="w-full max-w-md rounded-lg bg-white p-8 shadow-lg">
                <div className="space-y-6 text-center">
                    <div className="space-y-2">
                        <div className="flex justify-center">
                            <div className="rounded-full bg-blue-600 p-4">
                                <svg className="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m7.548-4.548a.75.75 0 00-1.06 0L12 8.94m0 0a.75.75 0 00-1.06 0M9 12m7.548-4.548L21 7m0 0L12.452 2.452m0 0a.75.75 0 10-1.06 0M21 7v10.5a.75.75 0 01-.75.75H3.75A.75.75 0 013 17.5V7m18 0h-3.5" />
                                </svg>
                            </div>
                        </div>
                        <h2 className="text-xl font-semibold text-gray-900">Redirecionando para SISP</h2>
                        <p className="text-sm text-gray-600">Você será redirecionado para o portal seguro de pagamentos</p>
                    </div>

                    <div className="rounded-lg bg-gray-50 p-4 text-left">
                        <div className="flex items-start gap-3">
                            <svg className="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                            </svg>
                            <div className="text-sm">
                                <p className="mb-1 font-medium text-gray-900">Transação Segura</p>
                                <ul className="space-y-1 text-xs text-gray-600">
                                    <li>• Portal oficial do Sistema Bancário de Cabo Verde</li>
                                    <li>• Criptografia SSL de nível bancário</li>
                                    <li>• Seus dados estão protegidos</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {countdown > 0 ? (
                        <div className="space-y-4">
                            <div className="rounded-lg bg-blue-50 p-4">
                                <p className="text-sm font-medium text-blue-700">
                                    Redirecionando em {countdown} segundo{countdown !== 1 ? 's' : ''}...
                                </p>
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <div className="rounded-lg bg-green-50 p-4">
                                <p className="text-sm font-medium text-green-700">Conectando ao SISP...</p>
                            </div>
                        </div>
                    )}
                </div>

                <form ref={formRef} action={endpoint} method="post" className="hidden">
                    {Object.entries(fields)
                        .map(([key, value]) => (
                            <input key={key} type="hidden" name={key} value={String(value)} />
                        ))}
                </form>
            </div>
        </div>
    );
}