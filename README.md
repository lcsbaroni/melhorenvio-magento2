# Melhor Envio - Magento 2
[![Build Status](https://img.shields.io/travis/lcsbaroni/melhorenvio-magento2/master.svg?style=flat-square)](https://travis-ci.org/lcsbaroni/melhorenvio-magento2)
[![Latest Stable Version](https://img.shields.io/packagist/v/lcsbaroni/melhorenvio-magento2.svg?style=flat-square)](https://packagist.org/packages/lcsbaroni/melhorenvio-magento2)
[![Total Downloads](https://img.shields.io/packagist/dt/lcsbaroni/melhorenvio-magento2.svg?style=flat-square)](https://packagist.org/packages/lcsbaroni/melhorenvio-magento2)

Modulo do melhor envio para magento 2

---
Descrição
---------
---
Com o módulo instalado e configurado, você pode oferecer como forma de envio todas as transportadoras integradas com o melhor envio. As funcionalidades do modulo são:

 - Cotação de frete (página do produto e carrinho) - OK
 - Finalizar pedido com frete do melhor envio - OK
 - Escolher agência para despachar pacote (jadlog por exemplo) - Não implementado, necessário fazer diretamente no painel do melhor envio
 - Comprar frete escolhido pelo cliente - Não implementado, necessário fazer diretamente no painel do melhor envio
 - Imprimir etiqueta de envio - Não implementado, necessário fazer diretamente no painel do melhor envio
 - Rastrear pedido - Não implementado, necessário fazer diretamente no painel do melhor envio


Requisitos
----------
---
 - [Magento] Community 2.1.* | 2.2.*
 - [PHP] 7.*

Instalação
-----------
> É altamente recomendado que você tenha um ambiente de testes para validar alterações e atualizações antes de atualizar sua loja em produção.

Navegue até o diretório raíz da sua instalação do Magento 2 e siga os seguintes passos:

> A instalação do módulo é feita utilizando o Composer. Para baixar e instalar o Composer no seu ambiente acesse https://getcomposer.org/download/ e caso tenha dúvidas de como utilizá-lo consulte a [documentação oficial do Composer](https://getcomposer.org/doc/).

1. Instale via packagist 
   - ```composer require lcsbaroni/melhorenvio-magento2```
       - Neste momento, podem ser solicitadas suas credenciais de autenticação do Magento. Caso tenha alguma dúvida, há uma descrição de como proceder nesse [link da documentação oficial](http://devdocs.magento.com/guides/v2.0/install-gde/prereq/connect-auth.html).
2. Execute os comandos:
   - ```php bin/magento setup:upgrade```
   - ```php bin/magento deploy:mode:set production```

3. Acesse a seção Lojas -> Configuração -> Vendas -> Métodos de entrega. Mapeie os atributos correspondentes a largura, altura, comprimento e peso de seus produtos, eles são utilizados para calcular o frete.

4. Selecione quais transportadoras você vai oferecer. Salve as alterações.

5. Pode ser necessário atualizar o cache da sua loja ao finalizar o processo.

Contribuições
-------------
---
Achou e corrigiu um bug ou tem alguma feature em mente e deseja contribuir?

* Faça um fork.
* Adicione sua feature ou correção de bug.
* Envie um pull request no [GitHub].
* Obs.: O Pull Request não deve ser enviado para o branch master e sim para o branch correspondente a versão ou para a branch de desenvolvimento.

  [Melhor Envio]: https://www.melhorenvio.com.br/
  [API Melhor Envio]: https://docs.melhorenvio.com.br/
  [Magento]: https://www.magentocommerce.com/
  [PHP]: http://www.php.net/
  [GitHub]: https://github.com/lcsbaroni/melhorenvio-magento2
