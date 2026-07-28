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
    toast.error(options.errorMessage ?? 'Something went wrong');
    return undefined;
  }
}
