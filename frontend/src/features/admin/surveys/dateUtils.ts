/**
 * Note: The backend currently operates on Asia/Tokyo timezone.
 * These helpers assume the database string is in 'YYYY-MM-DD HH:mm:ss' format (Asia/Tokyo)
 * and the browser 'datetime-local' input uses 'YYYY-MM-DDTHH:mm' (local time).
 *
 * In a production environment with multiple timezones, these should be updated
 * to use UTC for the database and explicit timezone conversions.
 */

/**
 * Converts a database date string (YYYY-MM-DD HH:mm:ss) to datetime-local input value (YYYY-MM-DDTHH:mm)
 */
export function toDatetimeLocal(dateStr: string | null | undefined): string {
  if (!dateStr) return '';
  // Input might already be in datetime-local format if it's from local state
  if (dateStr.includes('T')) return dateStr.substring(0, 16);

  // Replace space with T and strip seconds
  return dateStr.replace(' ', 'T').substring(0, 16);
}

/**
 * Converts a datetime-local input value (YYYY-MM-DDTHH:mm) to database date string (YYYY-MM-DD HH:mm:ss)
 */
export function fromDatetimeLocal(value: string | null | undefined): string | undefined {
  if (!value) return undefined;
  // If it's already in DB format, return as is (but ensure no T)
  if (!value.includes('T')) return value;

  return value.replace('T', ' ') + ':00';
}

/**
 * Formats a date string for display
 */
export function formatDisplayDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '-';
  return dateStr.substring(0, 16).replace('T', ' ');
}
