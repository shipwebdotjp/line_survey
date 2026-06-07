import liff from '@line/liff';

export async function initLiff(liffId: string): Promise<void> {
  try {
    await liff.init({ liffId });
    if (!liff.isInClient()) {
      // In a real application, you might want to allow external browser access for debugging
      // but according to the requirements, we should stop with an error.
      // throw new Error('Outside LIFF');
      throw new Error('Outside LIFF');
    }
  } catch (err) {
    console.error('LIFF initialization failed', err);
    throw err;
  }
}

export function isLiffEnabled(): boolean {
  return liff.isInClient();
}
