import Swal from 'sweetalert2'

// Custom theme matching the platform design
const swalTheme = {
    customClass: {
        popup: 'swal-popup',
        title: 'swal-title',
        htmlContainer: 'swal-content',
        confirmButton: 'swal-confirm-btn',
        cancelButton: 'swal-cancel-btn',
        actions: 'swal-actions',
    },
    buttonsStyling: false,
}

// Success alert
export function showSuccess(title, text = '') {
    return Swal.fire({
        ...swalTheme,
        icon: 'success',
        title,
        text,
        confirmButtonText: 'OK',
        timer: 3000,
        timerProgressBar: true,
    })
}

// Error alert
export function showError(title, text = '') {
    return Swal.fire({
        ...swalTheme,
        icon: 'error',
        title,
        text,
        confirmButtonText: 'OK',
    })
}

// Warning alert
export function showWarning(title, text = '') {
    return Swal.fire({
        ...swalTheme,
        icon: 'warning',
        title,
        text,
        confirmButtonText: 'OK',
    })
}

// Info alert
export function showInfo(title, text = '') {
    return Swal.fire({
        ...swalTheme,
        icon: 'info',
        title,
        text,
        confirmButtonText: 'OK',
    })
}

// Confirm dialog
export async function showConfirm(title, text = '', confirmText = 'Yes', cancelText = 'Cancel') {
    const result = await Swal.fire({
        ...swalTheme,
        icon: 'question',
        title,
        text,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true,
    })
    return result.isConfirmed
}

// Danger confirm (for destructive actions)
export async function showDangerConfirm(title, text = '', confirmText = 'Delete', cancelText = 'Cancel') {
    const result = await Swal.fire({
        ...swalTheme,
        icon: 'warning',
        title,
        text,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true,
        customClass: {
            ...swalTheme.customClass,
            confirmButton: 'swal-danger-btn',
        },
    })
    return result.isConfirmed
}

// Input dialog
export async function showInput(title, inputPlaceholder = '', inputType = 'text') {
    const result = await Swal.fire({
        ...swalTheme,
        title,
        input: inputType,
        inputPlaceholder,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter a value'
            }
        },
    })
    return result.isConfirmed ? result.value : null
}

// Loading state
export function showLoading(title = 'Please wait...') {
    Swal.fire({
        ...swalTheme,
        title,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading()
        },
    })
}

// Close loading
export function closeLoading() {
    Swal.close()
}

// Toast notification (alternative to showToast)
export function toast(title, icon = 'success') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
            popup: 'swal-toast',
        },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        },
    })

    return Toast.fire({
        icon,
        title,
    })
}

export default {
    showSuccess,
    showError,
    showWarning,
    showInfo,
    showConfirm,
    showDangerConfirm,
    showInput,
    showLoading,
    closeLoading,
    toast,
}
