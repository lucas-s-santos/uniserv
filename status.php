<?php
    const SERVICO_STATUS_FINALIZADO = 0;
    const SERVICO_STATUS_ATIVO = 1;
    const SERVICO_STATUS_PENDENTE = 2;
    const SERVICO_STATUS_RECUSADO = -1;
    const SERVICO_STATUS_CANCELADO = -2;

    function servico_status_label($status) {
        switch ((int)$status) {
            case SERVICO_STATUS_ATIVO:
                return 'Em andamento';
            case SERVICO_STATUS_PENDENTE:
                return 'Em pedido';
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
            case SERVICO_STATUS_FINALIZADO:
                return 'status-badge--done';
            case SERVICO_STATUS_RECUSADO:
            case SERVICO_STATUS_CANCELADO:
                return 'status-badge--canceled';
            default:
                return 'status-badge--pending';
        }
    }

    function servico_can_transition($fromStatus, $toStatus) {
        $fromStatus = (int)$fromStatus;
        $toStatus = (int)$toStatus;

        if ($fromStatus === SERVICO_STATUS_PENDENTE) {
            return in_array($toStatus, [SERVICO_STATUS_ATIVO, SERVICO_STATUS_RECUSADO, SERVICO_STATUS_CANCELADO], true);
        }
        if ($fromStatus === SERVICO_STATUS_ATIVO) {
            return in_array($toStatus, [SERVICO_STATUS_FINALIZADO, SERVICO_STATUS_CANCELADO], true);
        }
        return false;
    }
?>