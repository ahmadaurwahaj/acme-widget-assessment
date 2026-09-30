export type RemoteData<T> =
  { status: 'loading' } | { status: 'failed'; message: string } | { status: 'loaded'; response: T }
