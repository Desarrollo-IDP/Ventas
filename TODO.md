# TODO: Configurar Sistema en Puerto 1993 - ✅ COMPLETADO

## ✅ Pasos Completados
- [x] Crear TODO.md con pasos
- [x] 1. Editado httpd-vhosts.conf - Agregado VirtualHost *:1993 para P.V
app.url a http://localhost:1993/P.V
## Instrucciones del Usuario
## ⏳ Pasos Pendientes (Usuario)
- [ ] 3. **Reiniciar Apache**: XAMPP Control Panel → Apache Stop → Start
1. **Después de cada paso**: Reinicia Apache en XAMPP Control Panel (Stop → Start)
- [ ] 5. **Logs**: c:/xampp/apache/logs/P.V-1993-error.log y error.log

## Instrucciones Finales
1. Reinicia Apache ahora.
`http://13.0.0.49:1993/`
3. Si puerto ocupado: `netstat -ano | findstr :1993` → `taskkill /PID [numero] /F`
4. ¡Listo! Sistema POS ahora en puerto 1993.
2. **Prueba**: Abre http://localhost:1993/ en el navegador
3. **Si hay error de puerto**: Ejecuta `netstat -ano | findstr :1993` y mata procesos con `taskkill /PID [numero] /F`
4. **Logs**: Revisa c:/xampp/apache/logs/error.log si hay problemas
