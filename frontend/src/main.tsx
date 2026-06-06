import { StrictMode, useEffect } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.tsx'
import { initLiff } from './features/liff/liff-init'

const Main = () => {
  useEffect(() => {
    // Replace with your actual LIFF ID from environment variable later
    const liffId = '';
    if (liffId) {
      initLiff(liffId).catch(console.error);
    }
  }, []);

  return (
    <StrictMode>
      <App />
    </StrictMode>
  );
};

createRoot(document.getElementById('root')!).render(<Main />)
