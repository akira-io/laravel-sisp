import {useEffect} from 'react';
import {useForm} from '@inertiajs/react';

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
    };
}

interface PaymentResponseProps {
    transaction: TransactionData;
    error?: ErrorData | null;
    translations?: Translations;
    allowRetry?: boolean;
    invoice?: InvoiceData | null;
    payload: Record<string, any>;
}

const DEFAULT_TRANSLATIONS: Translations = {
    payment: {
        success_title: 'Payment Successfully Completed!',
        success_message: 'Your transaction has been processed successfully.',
        success_status: 'Completed',
        failed_title: 'Payment Declined',
        failed_message: 'Sorry, your payment was not processed.',
        failed_status: 'Failed',
        pending_title: 'Payment Pending',
        pending_message: 'Your payment is being processed.',
        pending_status: 'Pending',
        pending_note: 'You will receive a confirmation shortly. Please do not close this page.',
        reference: 'Reference',
        amount: 'Amount',
        status: 'Status',
        category: 'Category',
        reason: 'Reason',
        action: 'Action',
        invoice_download: 'Download Invoice',
        back_home: 'Back to Home',
        declined: 'Declined',
        retry_payment: 'Try Again',
        cancel_payment: 'Cancel',
    },
};

export default function PaymentResponse({
                                            transaction,
                                            error,
                                            translations = DEFAULT_TRANSLATIONS,
                                            allowRetry = true,
                                            invoice,
                                            payload
                                        }: PaymentResponseProps) {
    const isSuccess = transaction.status === 'completed';
    const isFailed = transaction.status === 'failed';
    const isPending = transaction.status === 'pending';
    const t = translations.payment;

    const {post, processing} = useForm({
        transaction_id: transaction.id,
    });

    const handleRetryPayment = () => {
        post('/sisp/retry-payment');
    };

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
                                <svg className='h-8 w-8 text-green-600 dark:text-green-500'
                                     fill='currentColor'
                                     viewBox='0 0 20 20'>
                                    <path fillRule='evenodd'
                                          d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z'
                                          clipRule='evenodd'/>
                                </svg>
                            </div>
                        </div>
                        <h2 className='text-2xl font-bold text-green-600 dark:text-green-500'>{t.success_title}</h2>
                        <p className='text-muted-foreground'>{t.success_message}</p>
                    </div>
                    <div className='space-y-2 rounded-lg bg-green-50 dark:bg-green-950 p-4 text-sm text-foreground border border-green-200 dark:border-green-800'>
                        <p><strong>{t.reference}:</strong> {transaction.merchant_ref}</p>
                        <p><strong>{t.amount}:</strong> {transaction.formatted_amount}</p>
                        <p><strong>{t.status}:</strong>
                            <span className='text-green-600 dark:text-green-500 font-medium ml-1'>{t.success_status}</span>
                        </p>
                    </div>
                    {invoice && invoice.pdf_url && (
                      <a href={invoice.pdf_url}
                         target='_blank'
                         download={`${invoice.invoice_number}.pdf`}
                         className='block w-full rounded-lg bg-blue-50 dark:bg-blue-950 hover:bg-blue-100 dark:hover:bg-blue-900 px-4 py-2 text-center text-sm font-medium text-blue-600 dark:text-blue-500 transition border border-blue-200 dark:border-blue-800'>
                          {t.invoice_download}
                      </a>
                    )}
                </div>
              )}
              {isFailed && (
                <div className='space-y-6'>
                    <div className='space-y-2 text-center'>
                        <div className='flex justify-center'>
                            <div className='rounded-full bg-red-100 dark:bg-red-950 p-4'>
                                <svg className='h-8 w-8 text-red-600 dark:text-red-500'
                                     fill='currentColor'
                                     viewBox='0 0 20 20'>
                                    <path fillRule='evenodd'
                                          d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z'
                                          clipRule='evenodd'/>
                                </svg>
                            </div>
                        </div>
                        <h2 className='text-2xl font-bold text-red-600 dark:text-red-500'>{t.failed_title}</h2>
                        <p className='text-muted-foreground'>{t.failed_message}</p>
                    </div>
                    <div className='space-y-2 rounded-lg bg-red-50 dark:bg-red-950 p-4 text-sm text-foreground border border-red-200 dark:border-red-800'>
                        <p><strong>{t.reference}:</strong> {transaction.merchant_ref || t.declined}</p>
                        {error && (
                          <>
                              <p><strong>{t.category}:</strong> {error.categoryLabel}</p>
                              <p><strong>{t.reason}:</strong> {error.label}</p>
                              <p><strong>{t.action}:</strong> {error.actionLabel}</p>
                          </>
                        )}
                        <p><strong>{t.status}:</strong>
                            <span className='text-red-600 dark:text-red-500 font-medium'>{t.failed_status}</span></p>
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
                        <h2 className='text-2xl font-bold text-yellow-600 dark:text-yellow-500'>{t.pending_title}</h2>
                        <p className='text-muted-foreground'>{t.pending_message}</p>
                    </div>
                    <div className='space-y-2 rounded-lg bg-yellow-50 dark:bg-yellow-950 p-4 text-sm text-foreground border border-yellow-200 dark:border-yellow-800'>
                        <p><strong>{t.reference}:</strong> {transaction.merchant_ref}</p>
                        <p><strong>{t.status}:</strong>
                            <span className='text-yellow-600 dark:text-yellow-500 font-medium'>{t.pending_status}</span>
                        </p>
                    </div>
                    <p className='text-center text-xs text-muted-foreground'>
                        {t.pending_note}
                    </p>
                </div>
              )}
              <div className='mt-8 space-y-2'>
                  {isFailed && allowRetry && (
                    <button
                      onClick={handleRetryPayment}
                      disabled={processing}
                      className='block w-full rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed px-4 py-2 text-center font-medium text-white transition'>
                        {processing ? 'Loading...' : t.retry_payment}
                    </button>
                  )}
                  <a href='/'
                     className={`block w-full rounded-lg px-4 py-2 text-center font-medium transition ${
                       isFailed ? 'bg-gray-200 hover:bg-gray-300 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100' : 'bg-primary hover:bg-primary/90 text-primary-foreground'
                     }`}>
                      {isFailed ? t.cancel_payment : t.back_home}
                  </a>
              </div>
          </div>
      </div>
    );
}