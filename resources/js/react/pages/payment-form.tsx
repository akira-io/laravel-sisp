import {useEffect, useRef, useState} from 'react';

interface PaymentFormProps {
    endpoint: string;
    fields: Record<string, string | number>;
}

export default function PaymentForm({endpoint, fields}: PaymentFormProps) {
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

    return (
      <div className='flex min-h-screen items-center justify-center bg-background'>
          <div className='w-full max-w-md rounded-lg bg-card p-8 shadow-lg border border-border'>
              <div className='space-y-6 text-center'>
                  <div className='space-y-2'>
                      <div className='flex justify-center'>
                          <div className='rounded-full bg-primary p-4'>
                              <svg className='h-8 w-8 text-primary-foreground' viewBox='0 0 24 24' fill='none' stroke='currentColor' strokeLinecap='round' strokeLinejoin='round' strokeWidth='1.5'>
                                  <path d='M22 11.429V18a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-1.5m17-5.071V10a2 2 0 0 0-2-2h-1m3 3.429h-3'/>
                                  <path d='M19 8v6.5a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2zm0 0H5.5'/>
                              </svg>
                          </div>
                      </div>
                      <h2 className='text-xl font-semibold text-foreground'>Redirecionando para SISP</h2>
                      <p className='text-sm text-muted-foreground'>Você será redirecionado para o portal seguro de pagamentos</p>
                  </div>
                  <div className='rounded-lg bg-muted p-4 text-left border border-border'>
                      <div className='flex items-start gap-3'>
                          <svg className='mt-0.5 h-5 w-5 flex-shrink-0 text-green-600 dark:text-green-500'
                               fill='currentColor'
                               viewBox='0 0 20 20'>
                              <path fillRule='evenodd'
                                    d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z'
                                    clipRule='evenodd'/>
                          </svg>
                          <div className='text-sm'>
                              <p className='mb-1 font-medium text-foreground'>Transação Segura</p>
                              <ul className='space-y-1 text-xs text-muted-foreground'>
                                  <li>• Portal oficial do Sistema Bancário de Cabo Verde</li>
                                  <li>• Criptografia SSL de nível bancário</li>
                                  <li>• Seus dados estão protegidos</li>
                              </ul>
                          </div>
                      </div>
                  </div>
                  {countdown > 0 ? (
                    <div className='space-y-4'>
                        <div className='rounded-lg bg-blue-50 dark:bg-blue-950 p-4 border border-blue-200 dark:border-blue-800'>
                            <p className='text-sm font-medium text-blue-700 dark:text-blue-300'>
                                Redirecionando em {countdown} segundo{countdown !== 1 ? 's' : ''}...
                            </p>
                        </div>
                    </div>
                  ) : (
                    <div className='space-y-4'>
                        <div className='rounded-lg bg-green-50 dark:bg-green-950 p-4 border border-green-200 dark:border-green-800'>
                            <p className='text-sm font-medium text-green-700 dark:text-green-300'>Conectando ao SISP...</p>
                        </div>
                    </div>
                  )}
              </div>
              <form ref={formRef} action={endpoint} method='post' className='hidden'>
                  {Object.entries(fields)
                    .map(([key, value]) => (
                      <input key={key} type='hidden' name={key} value={String(value)}/>
                    ))}
              </form>
          </div>
      </div>
    );
}