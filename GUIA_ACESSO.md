# Vehicle Tracker Pro - Guia de Acesso

## Para Recuperar Acesso ao WordPress

Como a imagem mostra um erro de senha na conta `financeiro@locminas.com.br`, siga estes passos:

### Opção 1: Recuperar Senha via Email

1. Na página de login (`http://locminas.com.br/wp-login.php`)
2. Clique em "Perdeu a senha?" (como mostrado na imagem)
3. Digite o e-mail ou nome de usuário: `financeiro`
4. Verifique seu email para o link de redefinição
5. Defina uma nova senha

### Opção 2: Redefinir via Banco de Dados (Alternativa)

Se o email não funcionar, você pode usar PHP:

```php
<?php
// Coloque este código temporariamente em wp-config.php ou em um arquivo separado

$user = get_user_by('email', 'financeiro@locminas.com.br');
if ($user) {
    wp_set_password('nova_senha_aqui', $user->ID);
    echo 'Senha redefinida com sucesso!';
} else {
    echo 'Usuário não encontrado!';
}
?>
```

### Opção 3: Usar WP-CLI (Se disponível)

```bash
wp user update financeiro --prompt=user_pass
```

---

## Após Acessar o WordPress

### Ativar o Plugin Vehicle Tracker

1. **Dashboard** → **Plugins**
2. Procure por "Vehicle Tracker Pro"
3. Clique em **Ativar**

### Configurar o Plugin

1. Vá para **Dashboard** → **Rastreamento** → **Configurações**
2. Preencha:
   - **API Avatek**: Suas credenciais (opcional)
   - **Servidor TCP**: Host e porta
   - **Google Maps**: Chave de API (opcional)
   - **Alertas**: Configure o que deseja monitorar

### Usar o Plugin

| Página | Função |
|--------|--------|
| **Dashboard** | Visão geral e estatísticas |
| **Tempo Real** | Rastreamento ao vivo dos veículos |
| **Veículos** | Gerenciar lista de veículos |
| **Histórico** | Consultar rotas passadas |
| **Geocercas** | Definir áreas de alerta |
| **Relatórios** | Análises e relatórios |
| **Configurações** | Ajustes do sistema |

---

## Melhorias Aplicadas ao Código

✅ **Segurança**: Verificações de permissão (capabilities)  
✅ **Validação**: Inputs sanitizados e validados  
✅ **Erros**: Tratamento com try/catch e mensagens úteis  
✅ **Layout**: CSS moderno e responsivo  
✅ **Performance**: Cache de opções e lazy loading  
✅ **Acessibilidade**: Suporte a traduções (i18n)  

---

## Estrutura de Arquivos

```
old_vehicle-tracker/
├── vehicle-tracker.php          ← Plugin principal (CORRIGIDO)
├── CORRECCOES.md               ← Detalhes das correções
├── admin/
│   ├── class-vt-admin.php
│   ├── class-vt-admin-dashboard.php
│   ├── class-vt-admin-vehicles.php
│   ├── class-vt-admin-tracking.php
│   ├── class-vt-admin-history.php
│   ├── class-vt-admin-geofences.php
│   ├── class-vt-admin-reports.php
│   └── class-vt-admin-settings.php
├── includes/
│   ├── class-vt-database.php
│   ├── class-vt-vehicle.php
│   ├── class-vt-alert.php
│   ├── class-vt-geofence.php
│   ├── class-vt-avatek-api.php
│   ├── class-vt-suntech-parser.php
│   ├── class-vt-tcp-server.php
│   └── class-vt-rest-api.php
├── assets/
│   ├── css/
│   │   ├── admin-style.css      ← Estilos admin (melhorado)
│   │   └── public-style.css     ← Estilos público
│   └── js/
│       ├── admin-script.js
│       └── public-script.js
├── public/
│   └── shortcode-tracker.php    ← Shortcode para frontend
├── languages/                   ← Traduções (i18n)
└── README.md
```

---

## Notas Importantes

⚠️ **Antes de usar em produção:**
1. Teste em ambiente de desenvolvimento
2. Faça backup do banco de dados
3. Configure as credenciais de API corretamente
4. Verifique permissões de arquivo

🔒 **Segurança:**
- Altere senhas padrão
- Use chaves de API seguras
- Habilite HTTPS
- Configure certificado SSL

📚 **Documentação:**
- Veja [CORRECCOES.md](CORRECCOES.md) para detalhes técnicos
- Consulte comentários no código PHP
- Referência WordPress: https://developer.wordpress.org/plugins/

---

**Status**: ✅ Plugin corrigido e pronto para uso  
**Última Atualização**: 30 de janeiro de 2026  
**Versão**: 1.0.0
