import type { HandleAsyncOptions } from '@/integrations/types';
import { toast } from 'sonner';

export async function handleAsync<T>(
  action: () => Promise<T>,
  options: HandleAsyncOptions = {}
): Promise<T | undefined> {
  try {
    const result = await action();

    if (options.successMessage) {
      toast.success(options.successMessage);
    }

    options.onSuccess?.();

    return result;
  } catch (error) {
    console.log(error);
    const message = error instanceof Error && error.message
    ? error.message
    : options.errorMessage ?? 'Something went wrong';

    toast.error(message);
    return undefined;
  }
}
