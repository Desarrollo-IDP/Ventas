# TODO: Fix Repositorio - Subir Sistema POS

Estado: ✅ **Progreso** (3/5 completado)

## Plan Aprobado (5 pasos)

**✅ Paso 0:** Usuario confirmó URL: `https://github.com/Desarrollo-IDP/Punto-de-venta.git`

**✅ Paso 1: Limpiar submódulo** `[x]` (submódulo eliminado)
```bash
git restore vendor/phpmailer/phpmailer
rmdir /s vendor/phpmailer/phpmailer
git rm --cached vendor/phpmailer/phpmailer
git status  # Verificar limpio
```

**✅ Paso 2: Composer install** `[x]` (vendor regenerado)
```bash
composer install
git add composer.lock
git status
```

**✅ Paso 3: .gitignore creado** `[x]` (protege 50+ archivos sensibles)
- Crear/editar `.gitignore` con vendor/, logs, backups, etc.

### **Paso 4: Configurar remote y push inicial** `[ ]`
```bash
git remote add origin https://github.com/Desarrollo-IDP/Punto-de-venta.git
git branch -M main
git push -u origin main
```

### **Paso 5: Commit final limpio** `[ ]`
```bash
git add .
git commit -m "Fix: Clean repo - proper vendor/Composer, .gitignore, no submódulos"
git push
```

---

**Comandos para ejecutar paso a paso.** Marcar [x] cada paso completado.

