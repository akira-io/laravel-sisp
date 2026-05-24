export interface TransactionData {
    id: number;
    status: 'completed' | 'failed' | 'pending';
    amount: number;
    formatted_amount: string;
    currency: string;
    merchant_ref: string;
    merchant_session: string;
    message_type?: string;
}

export interface ErrorData {
    code: string;
    label: string;
    category: string;
    categoryLabel: string;
    action: string;
    actionLabel: string;
}

export interface InvoiceData {
    invoice_number: string;
    pdf_url: string;
}

export interface Translations {
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

export const DEFAULT_TRANSLATIONS: Translations = {
    payment: {
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
    },
};
