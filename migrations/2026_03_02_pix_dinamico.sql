-- Execute uma unica vez no banco relampagoservice
-- Adiciona colunas para cobranca Pix dinamica e webhook de confirmacao automatica

ALTER TABLE servico
    ADD COLUMN pix_txid VARCHAR(35) NULL AFTER pagamento_data,
    ADD COLUMN pix_payload TEXT NULL AFTER pix_txid,
    ADD COLUMN pix_qr_url VARCHAR(500) NULL AFTER pix_payload,
    ADD COLUMN pix_gateway VARCHAR(40) NULL AFTER pix_qr_url,
    ADD COLUMN pix_expira_em DATETIME NULL AFTER pix_gateway,
    ADD COLUMN pix_status VARCHAR(20) NULL AFTER pix_expira_em,
    ADD COLUMN pix_valor DECIMAL(10,2) NULL AFTER pix_status,
    ADD COLUMN pix_pago_em DATETIME NULL AFTER pix_valor,
    ADD COLUMN pix_webhook_payload LONGTEXT NULL AFTER pix_pago_em;

ALTER TABLE servico
    ADD INDEX idx_servico_pix_txid (pix_txid),
    ADD INDEX idx_servico_pix_status (pix_status);
