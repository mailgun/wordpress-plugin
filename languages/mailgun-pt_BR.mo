��    T      �  q   \            !  �   )  6   �  V   �  �   >  �   	  [   �	  j   *
     �
     �
  I   �
  �   �
     �     �     �     �       	   	            	   (  	   2     <  �   E  }   �  g   ]  �   �  f   �            g   -     �     �     �     �     �     �     �  �        �  &   �     �     �                     )  %   2  $   X  %   }     �     �     �     �     �  �   �  �   �     y  	   �  	   �     �     �     �  
   �     �  n   �    J  �   j  �        �  d   �     a     t     �     �  J   �     �  �   �     �  6   �       [   1  9   �    �     �  �   �  A   �  x   �  �   c  �   C  _     z   }     �        W   $   �   |      S!     d!     r!     �!     �!  	   �!     �!     �!     �!     �!     �!  �   �!  �   �"  u   G#  "  �#  t   �$     U%     f%  �   |%  "   &     0&     7&     ?&     P&     k&  &   }&  �   �&     1'  D   N'     �'     �'     �'     �'  (   �'     (     (     )(  0   H(     y(     �(     �(     �(     �(  �   �(  �   �)     Y*  	   b*     l*     x*     �*     �*     �*     �*     �*  E  J+  �   �,  �   q-     X.  �   l.     /     $/     4/     B/  C   S/     �/    �/     �0  K   �0     1  v   1  K   �1            -   4   L                       5   G   I   1   8   D      .   E      7   O   F                   *   A      N      "   +   %   $              =      !         B      ,              S              &              0          H          C   R      K   3   M   (   @   Q   	       :   
   #          J   )       9             2   P      T      6      <                  '           ;       >   ?       /                via %s "Override From" option requires that "From Name" and "From Address" be set to work properly! <a href="%1$s">Configure Mailgun now</a>. <a href="%1$s" target="%2$s">View available lists</a>. <a href="%1$s" target="%3$s">Tracking</a> and <a href="%2$s" target="%3$s">Tagging</a> <div id="message" class="updated fade"><p>The lists page for the <strong>Mailgun</strong> plugin cannot be displayed. The file <strong>%s</strong> is missing.  Please reinstall the plugin.</p></div> <div id="message" class="updated fade"><p>The options page for the <strong>Mailgun</strong> plugin cannot be displayed. The file <strong>%s</strong> is missing.  Please reinstall the plugin.</p></div> <strong>Example:</strong> <code>[mailgun id="list1@mydomain.com,list2@mydomain.com"]</code> A <a href="%1$s" target="%2$s">Mailgun</a> account is required to use this plugin and the Mailgun service. API Key Available Mailing Lists Before attempting to test the configuration, please click "Save Changes". Choose a region - U.S./North America or Europe - from which to send email, and to store your customer data. Please note that your sending domain must be set up in whichever region you choose. Click Tracking Collect name: Configuration Description (optional): Europe Example:  Failure From Address From Name HTML Only HTTP API If added, this tag will exist on every outbound message. Statistics will be populated in the Mailgun Control Panel. Use a comma to define multiple tags.  If enabled, HTML messages will include an open tracking beacon. <a href="%1$s" target="%2$s">Open Tracking Documentation</a>. If enabled, Mailgun will and track links. <a href="%1$s" target="%2$s">Open Tracking Documentation</a>. If enabled, all emails will be sent with the above "From Name" and "From Address", regardless of values set by other plugins. Useful for cases where other plugins don't play nice with our "From Name" / "From Address" setting. If you need to register for an account, you can do so at <a href="%1$s" target="%2$s">Mailgun.com</a>. Insecure SMTP Learn more about Leave this at "TLS" unless mail sending fails. This option only matters for Secure SMTP. Default "TLS". List addresses (required): Lists Mailgun Mailgun Domain Name Mailgun List Widget Mailgun Lists Mailgun WordPress Plugin Test Mailgun has been automatically deactivated because the file <strong>%s</strong> is missing. Please reinstall the plugin and reactivate. Mailgun is almost ready.  Mailgun list ID needed to render form! Mailgun list widget New list_address No Open Tracking Override "From" Details Password Please enter your subscription email. Please enter your subscription name. Please select a list to subscribe to. Region has not been selected Save Changes Secure SMTP Security Type Select Your Region Set this to "No" if your server cannot establish SSL SMTP connections or if emails are not being delivered. If you set this to "No" your password will be sent in plain text. Only valid for use with SMTP. Default "Yes". Set this to "No" if your server cannot make outbound HTTP connections or if emails are not being delivered. "No" will cause this plugin to use SMTP. Default "Yes". Settings Shortcode Subscribe Success Tag Test Configuration Testing... Thank you for subscribing! The "User Name" part of the sender information (<code>"Excited User &lt;user@samples.mailgun.org&gt;"</code>). The &lt;address@mydomain.com&gt; part of the sender information (<code>"Excited User &lt;user@samples.mailgun.org&gt;"</code>). This address will appear as the `From` address on sent mail. <strong>It is recommended that the @mydomain portion matches your Mailgun sending domain.</strong> The Mailgun plugin configuration has changed since you last saved. Do you wish to test anyway?\n\nClick "Cancel" and then "Save Changes" if you wish to save your changes. This is a test email generated by the Mailgun WordPress plugin.

If you have received this message, the requested test has succeeded.

The sending region is set to %s.

The method used to send this email was: %s. Title (optional): To allow users to subscribe to multiple lists on a single form, comma-separate the Mailgun list ids. U.S./North America Unauthorized Use HTTP API Use Secure SMTP Use the shortcode above to associate a widget instance with a mailgun list Username With the latest update, Mailgun now supports multiple regions! By default, we will use the U.S. region, but we now have an EU region available. You can configure your Mailgun settings <a href="%1$s">here</a> or in your wp-config.php. Yes Your Mailgun API key. Only valid for use with the API. Your Mailgun Domain Name. Your Mailgun SMTP password that goes with the above username. Only valid for use with SMTP. Your Mailgun SMTP username. Only valid for use with SMTP. Project-Id-Version: Mailgun 1.5.7
Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/mailgun
POT-Creation-Date: 2018-12-09 12:34-0200
PO-Revision-Date: 2018-12-09 13:04-0200
Last-Translator: Emerson Broga <emerson.broga@gmail.com>
Language-Team: Mailgun <support@mailgun.com>
Language: pt_BR
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
X-Generator: Poedit 2.2
Plural-Forms: nplurals=2; plural=(n > 1);
X-Poedit-KeywordsList: __;_e;__
X-Poedit-Basepath: ..
X-Poedit-SearchPath-0: .
  via %s “Sobrescrever remetente” requer que  “Nome do remetente” and “Email do remetente” estejam atribuídos para funcionar corretamente! <a href=“%1$s”>Configure o Mailgun agora</a>. <a href=“%1$s” target=“%2$s”>Ver listas disponíveis</a>. <a href=“%1$s” target=“%3$s”>Rastrear (Tracking)</a> e <a href=“%2$s” target=“%3$s”>Marcar (tagging)</a> <div id=“message” class=“updated fade”><p>A página de listas do plugin do <strong>Mailgun</strong> não pôde ser exibida. O arquivo <strong>%s</strong> não foi encontrado.  Por favor reinstale o plugin.</p></div> <div id="message" class="updated fade"><p>A página de opções do plugin do <strong>Mailgun</strong> não pôde ser exibida. O arquivo <strong>%s</strong> não foi encontrado.  Por favor reinstale o plugin.</p></div> <strong>Exemplo:</strong> <code>[mailgun id=“list1@mydomain.com,list2@mydomain.com”]</code> A <a href=“%1$s” target=“%2$s”>Conta do Mailgun</a> é requerida para o uso desse plugin e do serviço do Mailgun. Chave de API Listas de Mailing Disponíveis Antes de tentar testar a configuração, por favor clique em “Salvar Alterações”. Selecione a região - Estados Unidos/America do Norte ou Europa - de onde enviar o email, e lavar os dados do usuário. Tenha em mente que a o domínio de envio deve estar configurado na região que você escolher. Rastrear Cliques Coletar nome: Configuração Descrição (opcional): Europa Exemplo:  Falha Email do remetente Nome do remetente Somente HTML HTTP API Se habilitado, essa marcação (tag) vai existir em todos os email de saída. Estatísticas eram populadas no Painel de Controle do Mailgun. Use vírgula para definir marcações (tags) múltiplas.  Se habilitado, mensagens HTML vão incluir o beacon de rastreamento. <a href=“%1$s” target=“%2$s”>Abrir documentação de rastreamento.</a>. Se habilitado, Mailgun vai ratear links. <a href=“%1$s” target=“%2$s”>Ver documentação de rastreamento</a>. Se habilitado, todos os emails serão enviados com o “Nome de Remetente” e o “Email de Remetente” acima, independente dos valores atribuídos por outros plugins. Útil para os casos onde os outros plugins não funcionam muito bem com as configurações de Nome e Email do Remetente. Se você precisa de criar uma conta, você pode fazer isso no  <a href=“%1$s” target=“%2$s”>Mailgun.com</a>. Usar SMPT Seguro Saiba mais a respeito Deixe como  “TLS” a não ser que o envio de emails esteja falhando. Está opção somente é importante para SMTP Seguro. Padrão “TLS”. Endereço da lista (obrigatório): Listas Mailgun Domínio Mailgun Widget de Lista do Mailgun Listas do Mailgun Teste do Plugin Mailgun para WordPress Mailgun foi desativado automaticamente porque está faltando o arquivo <strong>%s</strong>. Por favor re-instale o plugin e ative novamente. Mailgun está quase pronto.  ID da lista do Mailgun é necessária para renderizar o formulário! Widget de lista do Mailgun Novo endereço de lista Não Abrir rastreamento Sobrescrever detalhes do “Remetente” Senha Por favor preencha o email. Por favor preencha o seu nome. Por favor selecione uma lista para se inscrever. Região não foi selecionada Salvar Alterações SMPT Seguro Tipo de segurança Selecione sua região Coloque “Não” se o seu servidor não puder fazer requisições HTTP de saída ou se os emails não estão sendo entregues.  “Não” vai fazer o plugin usar  SMTP. Padão “Sim”. Coloque “Não” se o seu servidor não puder fazer requisições HTTP de saída ou se os emails não estão sendo entregues.  “Não” vai fazer o plugin usar  SMTP. Padão “Sim”. Opções Shortcode Inscreva-se Sucesso Marcar (Tag) Testar Configuração Testando… Obrigado por se inscrever! O “Nome de usuário” é parte da informação do remetente. (<code>”Usuário &lt;user@samples.mailgun.org&gt;”</code>). O &lt;address@mydomain.com&gt; é parte da informação do rementente (<code>”Usuário &lt;user@samples.mailgun.org&gt;”</code>). Esse endereço de email vai aparecer como o “Email do remetente” nos emails enviados.<strong>É recomendado que a parte @mydomain seja a mesma que o domínio de envio do Mailgun.</strong> As configurações do plugin do Mailgun foram alteradas desde a última vez que você salvou. Deseja testar assim mesmo?\n\nClique “Cancelar” e depois “Salvar Alterações” caso você deseje salvar suas alterações. Este é um email de teste gerado pelo plugin to Mailgun para Wordpress.

Se você recebeu esta mensagem, a requisição foi bem sucedida.

A região de envio está definida para %s.

O método usado para enviar esse email foi: %s. Título (opcional): Para permitir que os usuários se inscrevam em múltiplas listas usando um único formulário, coloque os ido do Mailgun separado por ponto e vírgula. Estados Unidos/America do Norte Não autorizado Usar API HTTP Usar SMTP Seguro Use o shortcode acima para associar o widget com a lista do mailgun Nome de usuário Com a ultima atualização do Mailgun, agora tem suporte a múltiplas regiões! Por padrão, nós vamos usar a região Estados Unidos, mas agora nos temos a região Europa disponível também. Você pode configurar  <a href=“%1$s”>here</a> ou no seu wp-config.php. Sim Seu nome de usuário do SMPT do Mailgun. Válido somente para uso com SMTP. Seu Domínio Mailgun. Sua senha de SMTP do Mailgun que é usada em conjunto com o nome de usuário acima. Válido somente para uso com SMTP. Seu nome de usuário do SMPT do Mailgun. Válido somente para uso com SMTP. 