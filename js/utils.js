export function showLoader(loader) { loader.style.display = 'inline'; }
export function hideLoader(loader) { loader.style.display = 'none'; }
export function confirmAction(message) { return confirm(message); }
export function gritter(title, message, className, sticky, time) {
    console.log(`${title}: ${message}`); // Replace with a proper gritter library if available
    setTimeout(() => console.clear(), time || 1000);
}
gritter.removeAll = () => console.clear();