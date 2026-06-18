/**
 * Formats a date string to YYYY/MM/DD HH:mm format.
 * Primarily used for survey response timestamps.
 */
export const formatHistoryDate = (value: string): string => {
  const directMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);

  if (directMatch) {
    const [, year, month, day, hour, minute] = directMatch;
    return `${year}/${month}/${day} ${hour}:${minute}`;
  }

  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return value;
  }

  const pad = (input: number): string => String(input).padStart(2, '0');
  return `${parsedDate.getFullYear()}/${pad(parsedDate.getMonth() + 1)}/${pad(parsedDate.getDate())} ${pad(parsedDate.getHours())}:${pad(parsedDate.getMinutes())}`;
};
