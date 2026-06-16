# Frontend Development Rules

## Confirmation Dialogs
Do **NOT** use `window.confirm()`. It has a known bug in iPhone Safari where it may not display after history transitions.
Instead, use the `useConfirm()` hook provided by `ConfirmProvider`.

Example:
```tsx
const confirm = useConfirm();

const handleDelete = async () => {
  if (await confirm({ message: 'Are you sure?', danger: true })) {
    // perform delete
  }
};
```

## Toast Notifications
Use `useToast()` to show feedback messages.
```tsx
const { showToast } = useToast();
showToast('Saved successfully');
```
