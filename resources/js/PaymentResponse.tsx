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

export default function PaymentResponse({ transaction, payload }: PaymentResponseProps) {
    const isSuccess = transaction.status === 'completed';
    const isFailed = transaction.status === 'failed';
    const isPending = transaction.status === 'pending';

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50">
            <div className="w-full max-w-md rounded-lg bg-white p-8 shadow-lg">
                {isSuccess && (
                    <div className="space-y-6">
                        <div className="space-y-2 text-center">
                            <div className="flex justify-center">
                                <div className="rounded-full bg-green-100 p-4">
                                    <svg className="h-8 w-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <h2 className="text-2xl font-bold text-green-600">Pagamento Realizado com Sucesso!</h2>
                            <p className="text-gray-600">Sua transação foi processada com sucesso.</p>
                        </div>

                        <div className="space-y-2 rounded-lg bg-green-50 p-4 text-sm text-gray-700">
                            <p><strong>ID da Transação:</strong> {transaction.id}</p>
                            <p><strong>Referência:</strong> {transaction.merchant_ref}</p>
                            <p><strong>Valor:</strong> {transaction.amount} {transaction.currency}</p>
                            <p><strong>Status:</strong> <span className="text-green-600 font-medium">Completado</span></p>
                        </div>
                    </div>
                )}

                {isFailed && (
                    <div className="space-y-6">
                        <div className="space-y-2 text-center">
                            <div className="flex justify-center">
                                <div className="rounded-full bg-red-100 p-4">
                                    <svg className="h-8 w-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <h2 className="text-2xl font-bold text-red-600">Pagamento Recusado</h2>
                            <p className="text-gray-600">Desculpe, seu pagamento não foi processado.</p>
                        </div>

                        <div className="space-y-2 rounded-lg bg-red-50 p-4 text-sm text-gray-700">
                            <p><strong>ID da Transação:</strong> {transaction.id}</p>
                            <p><strong>Motivo:</strong> {transaction.message_type || 'Recusado'}</p>
                            <p><strong>Status:</strong> <span className="text-red-600 font-medium">Falhou</span></p>
                        </div>
                    </div>
                )}

                {isPending && (
                    <div className="space-y-6">
                        <div className="space-y-2 text-center">
                            <div className="flex justify-center">
                                <div className="rounded-full bg-yellow-100 p-4">
                                    <svg className="h-8 w-8 text-yellow-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <h2 className="text-2xl font-bold text-yellow-600">Pagamento Pendente</h2>
                            <p className="text-gray-600">Seu pagamento está sendo processado.</p>
                        </div>

                        <div className="space-y-2 rounded-lg bg-yellow-50 p-4 text-sm text-gray-700">
                            <p><strong>ID da Transação:</strong> {transaction.id}</p>
                            <p><strong>Referência:</strong> {transaction.merchant_ref}</p>
                            <p><strong>Status:</strong> <span className="text-yellow-600 font-medium">Pendente</span></p>
                        </div>

                        <p className="text-center text-xs text-gray-500">
                            Você receberá uma confirmação em breve. Por favor, não feche esta página.
                        </p>
                    </div>
                )}

                <div className="mt-8">
                    <a href="/" className="block w-full rounded-lg bg-blue-600 px-4 py-2 text-center font-medium text-white hover:bg-blue-700 transition">
                        Voltar ao Início
                    </a>
                </div>
            </div>
        </div>
    );
}