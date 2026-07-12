import Modal from '@/Components/Modal';
import Button from '@/Components/ui/Button';
import { AlertTriangle } from 'lucide-react';

export default function ConfirmDialog({
    show,
    title,
    message,
    confirmLabel = 'Eliminar',
    processing = false,
    onConfirm,
    onClose,
}: {
    show: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    processing?: boolean;
    onConfirm: () => void;
    onClose: () => void;
}) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="p-6">
                <div className="flex items-start gap-4">
                    <div className="flex size-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-900/40">
                        <AlertTriangle className="size-5" />
                    </div>
                    <div className="flex-1">
                        <h3 className="text-base font-semibold text-slate-800 dark:text-slate-100">
                            {title}
                        </h3>
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {message}
                        </p>
                    </div>
                </div>
                <div className="mt-6 flex justify-end gap-2">
                    <Button variant="secondary" onClick={onClose} disabled={processing}>
                        Cancelar
                    </Button>
                    <Button variant="danger" onClick={onConfirm} disabled={processing}>
                        {confirmLabel}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
