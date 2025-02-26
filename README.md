# CrawlerX WP

Plugin WordPress para integração com o CrawlerX, permitindo postagem automática de conteúdo via API REST.

## 🚀 Funcionalidades

- API REST segura com autenticação via API Key
- Criação automática de posts com suporte a HTML
- Upload de imagens em base64 como imagem destacada
- Documentação interativa com Swagger UI
- Interface administrativa para gerenciamento

## 📋 Requisitos

- WordPress 5.0 ou superior
- PHP 7.4 ou superior

## 🔧 Instalação

1. Faça o upload do plugin para a pasta `/wp-content/plugins/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Acesse o menu 'CrawlerX WP' para obter sua API Key

## 🔑 Configuração

Após a instalação, você encontrará no painel administrativo:

- API Key para autenticação
- URL base da API
- Documentação Swagger interativa

## 🔒 Segurança

- Autenticação via header `X-API-Key`
- Sanitização de inputs
- Validação de permissões
- Proteção contra acesso direto aos arquivos

## 📄 Documentação

Documentação completa disponível via Swagger UI no painel administrativo do WordPress.

## 🛠️ Desenvolvimento

O plugin foi desenvolvido seguindo as melhores práticas do WordPress:
- Código organizado e documentado
- Hooks e filtros para extensibilidade
- Sanitização e validação de dados
- Padrões de segurança do WordPress

## 📄 Licença

Este projeto está sob a Licença MIT - veja o arquivo LICENSE para detalhes

## ✒️ Autor

Desenvolvido com ❤️ por [Lucas Trindade](https://github.com/trindadebra)
