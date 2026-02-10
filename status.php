<?php
    const SERVICO_STATUS_FINALIZADO = 0;
    const SERVICO_STATUS_ATIVO = 1;
    const SERVICO_STATUS_PENDENTE = 2;
    const SERVICO_STATUS_AGUARDANDO_PAGAMENTO = 3;
    const SERVICO_STATUS_RECUSADO = -1;
    const SERVICO_STATUS_CANCELADO = -2;

    const SERVICO_ETAPA_PENDENTE = 1;
    const SERVICO_ETAPA_ORCAMENTO = 2;
    const SERVICO_ETAPA_AGUARDANDO = 3;
    const SERVICO_ETAPA_EXECUCAO = 4;
    const SERVICO_ETAPA_FINALIZADO = 5;

    function servico_status_label($status) {
        switch ((int)$status) {
            case SERVICO_STATUS_ATIVO:
                return 'Em andamento';
            case SERVICO_STATUS_PENDENTE:
                return 'Em pedido';
            case SERVICO_STATUS_AGUARDANDO_PAGAMENTO:
                return 'Aguardando pagamento';
            case SERVICO_STATUS_FINALIZADO:
                return 'Finalizado';
            case SERVICO_STATUS_RECUSADO:
                return 'Recusado';
            case SERVICO_STATUS_CANCELADO:
                return 'Cancelado';
            default:
                return 'Desconhecido';
        }
    }

    function servico_status_badge_class($status) {
        switch ((int)$status) {
            case SERVICO_STATUS_ATIVO:
                return 'status-badge--active';
            case SERVICO_STATUS_PENDENTE:
                return 'status-badge--pending';
            case SERVICO_STATUS_AGUARDANDO_PAGAMENTO:
                return 'status-badge--pending';
            case SERVICO_STATUS_FINALIZADO:
                return 'status-badge--done';
            case SERVICO_STATUS_RECUSADO:
            case SERVICO_STATUS_CANCELADO:
                return 'status-badge--canceled';
            default:
                return 'status-badge--pending';
        }
    }

    function servico_etapa_label($etapa) {
        switch ((int)$etapa) {
            case SERVICO_ETAPA_PENDENTE:
                return 'Pendente';
            case SERVICO_ETAPA_ORCAMENTO:
                return 'Orcamento enviado';
            case SERVICO_ETAPA_AGUARDANDO:
                return 'Aguardando inicio';
            case SERVICO_ETAPA_EXECUCAO:
                return 'Em execucao';
            case SERVICO_ETAPA_FINALIZADO:
                return 'Finalizado';
            default:
                return 'Pendente';
        }
    }

    function servico_etapa_from_status($status) {
        switch ((int)$status) {
            case SERVICO_STATUS_ATIVO:
                return SERVICO_ETAPA_EXECUCAO;
            case SERVICO_STATUS_AGUARDANDO_PAGAMENTO:
                return SERVICO_ETAPA_FINALIZADO;
            case SERVICO_STATUS_FINALIZADO:
                return SERVICO_ETAPA_FINALIZADO;
            case SERVICO_STATUS_PENDENTE:
            default:
                return SERVICO_ETAPA_PENDENTE;
        }
    }

    function servico_etapa_steps() {
        return [
            SERVICO_ETAPA_PENDENTE,
            SERVICO_ETAPA_ORCAMENTO,
            SERVICO_ETAPA_AGUARDANDO,
            SERVICO_ETAPA_EXECUCAO,
            SERVICO_ETAPA_FINALIZADO
        ];
    }

    function servico_etapa_can_transition($fromEtapa, $toEtapa) {
        $fromEtapa = (int)$fromEtapa;
        $toEtapa = (int)$toEtapa;
        $steps = servico_etapa_steps();
        $fromIndex = array_search($fromEtapa, $steps, true);
        $toIndex = array_search($toEtapa, $steps, true);
        if ($fromIndex === false || $toIndex === false) {
            return false;
        }
        return $toIndex === $fromIndex + 1 || $toIndex === $fromIndex;
    }

    function servico_can_transition($fromStatus, $toStatus) {
        $fromStatus = (int)$fromStatus;
        $toStatus = (int)$toStatus;

        if ($fromStatus === SERVICO_STATUS_PENDENTE) {
            return in_array($toStatus, [SERVICO_STATUS_ATIVO, SERVICO_STATUS_RECUSADO, SERVICO_STATUS_CANCELADO], true);
        }
        if ($fromStatus === SERVICO_STATUS_ATIVO) {
            return in_array($toStatus, [SERVICO_STATUS_FINALIZADO, SERVICO_STATUS_CANCELADO, SERVICO_STATUS_AGUARDANDO_PAGAMENTO], true);
        }
        if ($fromStatus === SERVICO_STATUS_AGUARDANDO_PAGAMENTO) {
            return in_array($toStatus, [SERVICO_STATUS_FINALIZADO, SERVICO_STATUS_CANCELADO], true);
        }
        return false;
    }
?>