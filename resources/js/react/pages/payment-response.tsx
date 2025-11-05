import { useEffect } from 'react';

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

interface InvoiceData {
    invoice_number: string;
    pdf_path: string;
}

interface PaymentResponseProps {
    transaction: TransactionData;
    invoice?: InvoiceData | null;
    payload: Record<string, any>;
}

export default function PaymentResponse({transaction, invoice, payload}: PaymentResponseProps) {
    const isSuccess = transaction.status === 'completed';
    const isFailed = transaction.status === 'failed';
    const isPending = transaction.status === 'pending';

    useEffect(() => {
        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            e.preventDefault();
            e.returnValue = '';
            return '';
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
        };
    }, []);

    return (
      <div className='flex min-h-screen items-center justify-center bg-background'>
          <div className='w-full max-w-md rounded-lg bg-card p-8 shadow-lg border border-border'>
              {isSuccess && (
                <div className='space-y-6'>
                    <div className='space-y-2 text-center'>
                        <div className='flex justify-center'>
                            <div className='rounded-full bg-green-100 dark:bg-green-950 p-4'>
                                <svg className='h-8 w-8 text-green-600 dark:text-green-500' fill='currentColor' viewBox='0 0 20 20'>
                                    <path fillRule='evenodd'
                                          d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z'
                                          clipRule='evenodd'/>
                                </svg>
                            </div>
                        </div>
                        <h2 className='text-2xl font-bold text-green-600 dark:text-green-500'>Pagamento Realizado com Sucesso!</h2>
                        <p className='text-muted-foreground'>Sua transação foi processada com sucesso.</p>
                    </div>
                    <div className='space-y-2 rounded-lg bg-green-50 dark:bg-green-950 p-4 text-sm text-foreground border border-green-200 dark:border-green-800'>
                        <p><strong>Referência:</strong> {transaction.merchant_ref}</p>
                        <p><strong>Valor:</strong> {transaction.formatted_amount}</p>
                        <p><strong>Status:</strong> <span className='text-green-600 dark:text-green-500 font-medium'>Completado</span></p>
                    </div>
                    {invoice && invoice.pdf_path && (
                        <a href={`/storage/${invoice.pdf_path}`}
                           download={`${invoice.invoice_number}.pdf`}
                           className='block w-full rounded-lg bg-blue-50 dark:bg-blue-950 hover:bg-blue-100 dark:hover:bg-blue-900 px-4 py-2 text-center text-sm font-medium text-blue-600 dark:text-blue-500 transition border border-blue-200 dark:border-blue-800'>
                            Download da Fatura
                        </a>
                    )}
                </div>
              )}
              {isFailed && (
                <div className='space-y-6'>
                    <div className='space-y-2 text-center'>
                        <div className='flex justify-center'>
                            <div className='rounded-full bg-red-100 dark:bg-red-950 p-4'>
                                <svg className='h-8 w-8 text-red-600 dark:text-red-500' fill='currentColor' viewBox='0 0 20 20'>
                                    <path fillRule='evenodd'
                                          d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z'
                                          clipRule='evenodd'/>
                                </svg>
                            </div>
                        </div>
                        <h2 className='text-2xl font-bold text-red-600 dark:text-red-500'>Pagamento Recusado</h2>
                        <p className='text-muted-foreground'>Desculpe, seu pagamento não foi processado.</p>
                    </div>
                    <div className='space-y-2 rounded-lg bg-red-50 dark:bg-red-950 p-4 text-sm text-foreground border border-red-200 dark:border-red-800'>
                        <p><strong>Motivo:</strong> {transaction.message_type || 'Recusado'}</p>
                        <p><strong>Status:</strong> <span className='text-red-600 dark:text-red-500 font-medium'>Falhou</span></p>
                    </div>
                </div>
              )}
              {isPending && (
                <div className='space-y-6'>
                    <div className='space-y-2 text-center'>
                        <div className='flex justify-center'>
                            <div className='rounded-full bg-yellow-100 dark:bg-yellow-950 p-4'>
                                <svg className='h-8 w-8 text-yellow-600 dark:text-yellow-500 animate-spin'
                                     fill='none'
                                     stroke='currentColor'
                                     viewBox='0 0 24 24'>
                                    <path strokeLinecap='round'
                                          strokeLinejoin='round'
                                          strokeWidth={2}
                                          d='M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'/>
                                </svg>
                            </div>
                        </div>
                        <h2 className='text-2xl font-bold text-yellow-600 dark:text-yellow-500'>Pagamento Pendente</h2>
                        <p className='text-muted-foreground'>Seu pagamento está sendo processado.</p>
                    </div>
                    <div className='space-y-2 rounded-lg bg-yellow-50 dark:bg-yellow-950 p-4 text-sm text-foreground border border-yellow-200 dark:border-yellow-800'>
                        <p><strong>Referência:</strong> {transaction.merchant_ref}</p>
                        <p><strong>Status:</strong> <span className='text-yellow-600 dark:text-yellow-500 font-medium'>Pendente</span></p>
                    </div>
                    <p className='text-center text-xs text-muted-foreground'>
                        Você receberá uma confirmação em breve. Por favor, não feche esta página.
                    </p>
                </div>
              )}
              <div className='mt-8'>
                  <a href='/'
                     className='block w-full rounded-lg bg-primary hover:bg-primary/90 px-4 py-2 text-center font-medium text-primary-foreground transition'>
                      Voltar ao Início
                  </a>
              </div>
          </div>
      </div>
    );
}