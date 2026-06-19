// Small presentation helpers shared across views.

export function statusLabel(status) {
    return {
        scheduled: 'Abierta',
        locked: 'Cerrada',
        live: 'En vivo',
        finished: 'Finalizada',
    }[status] ?? status;
}

export function statusClasses(status) {
    return {
        scheduled: 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30',
        locked: 'bg-amber-500/15 text-amber-300 ring-amber-500/30',
        live: 'bg-rose-500/15 text-rose-300 ring-rose-500/30 animate-pulse',
        finished: 'bg-zinc-500/15 text-zinc-300 ring-zinc-500/30',
    }[status] ?? 'bg-zinc-500/15 text-zinc-300 ring-zinc-500/30';
}

// "in 2h 14m" style countdown text from an ISO date to now.
export function countdown(iso) {
    if (!iso) return '';
    const diff = new Date(iso).getTime() - Date.now();
    if (diff <= 0) return 'comenzado';
    const mins = Math.floor(diff / 60000);
    const d = Math.floor(mins / 1440);
    const h = Math.floor((mins % 1440) / 60);
    const m = mins % 60;
    if (d > 0) return `${d}d ${h}h`;
    if (h > 0) return `${h}h ${m}m`;
    return `${m}m`;
}

export function kickoffText(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString('es', {
        weekday: 'short', day: 'numeric', month: 'short',
        hour: '2-digit', minute: '2-digit',
    });
}
