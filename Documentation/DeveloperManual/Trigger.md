Trigger
=======

Trigger bilden in T3 SOCIALS die Schnittstelle zwischen einem Datensatz aus der TYPO3-Datenbank und T3 SOCIALS. Der Trigger für tt\_news beispielsweise, überwacht das System auf neue News, um diese an T3 SOCIALS zu übergeben und ggf. an ein oder mehrere Accounts zu verteilen.

Um ein Trigger für T3 SOCIALS zu registrieren, sind aktuell 2 Dinge notwendig:

1.  trigger-config
2.  trigger-message-builder

Configuration
-------------

Eine Konfiguration enthält im Wesentlichen die zu überwachende Tabelle und einen Message-Builder. Weiter wird die Konfiguration dazu verwendet, den Trigger bei T3 SOCIALS zu registrieren.

Die Konfiguration muss immer über das Basismodel *tx\_t3socials\_models\_TriggerConfig* geschehen.

Entweder man legt nun eine eigene Klasse an, welche von dem Basismodel erbt und die Konfiguration enthält, oder man nutzt einfach das Basismodel und setzt darin die Konfiguration.

Die Verwendung ist dabei identisch zu der network-config eines Netzwerks.

~~~~ {.sourceCode .php}
tx_rnbase::load('tx_t3socials_models_NetworkConfig');
class tx_t3socials_network_twitter_NetworkConfig
   extends tx_t3socials_models_NetworkConfig {
   protected function initConfig() {
      parent::initConfig();
      $this->setProperty('trigger_id', 'news');
      $this->setProperty('table', 'tt_news');
      $this->setProperty(
         'message_builder',
         'tx_t3socials_trigger_news_MessageBuilder'
      );
   }
}
~~~~

Mögliche Optionen der Konfiguration:

Property  
trigger\_id

Default  
Default ist der Wert der Eigenschaft *table*

Description  
Eine eindeutige ID für den Trigger.

Property  
table \*

Default  
NULL

Description  
Der Tabellenname, wessen Datensätze überwacht und genutzt werden sollen.

Property  
resolver

Default  
tx\_t3socials\_util\_ResolverT3DB

Description  
Der Resolver ist dafür Zuständig, aus einem Identifier (UID) und einem Tabellennamen einen Datensatz zu bilden. Der Resolver muss das Interface *tx\_t3socials\_util\_IResolver* implementieren. Defaultwert ist *tx\_t3socials\_util\_ResolverT3DB*.

Der T3DB Resolver sollte für die meisten Zwecke ausreichen. Er besorgt sich aus der Datenbank den Datensatz mit einer bestimmten UID aus einer bestimmten Tabelle.

Der gelieferte Datensatz muss in einem Model verpackt zurückgegeben werden. Das Modell muss eine Instanz der Klasse *tx\_t3socials\_models\_Base* sein. Beispielaufruf:

> ~~~~ {.sourceCode .php}
> $model = tx_rnbase::makeInstance('tx_t3socials_models_Base', array(/* Record */))
> $model->setTableName($tableName);
> ~~~~

Property  
message\_builder \*

Default  
NULL

Description  
Der Klassenname des Message Builders. Mehr dazu im Abschnitt trigger-message-builder

Mit \* markierte Felder sind Pflichtangaben!

Message-Builder
---------------

Der Message-Builder ist dafür zuständig, aus einem speziellen Datensatz, wie beispielsweise einer News, ein generisches Message-Model zu erzeugen und zu befüllen.

Der Message-Builder muss entweder von der Basisklasse *tx\_t3socials\_trigger\_MessageBuilder* erben, oder das Interface *tx\_t3socials\_trigger\_IMessageBuilder* implementieren.

Wir empfehlen, das direkt von der Basisklasse geerbt wird, da man sich dann nur noch um das Befüllen der Message kümmern muss.

Dazu muss dann lediglich die Methode *buildMessage* angelegt werden.

Hier ein kleines Beispiel für einen tt\_news Datensatz:

~~~~ {.sourceCode .php}
tx_rnbase::load('tx_t3socials_trigger_MessageBuilder');
class tx_t3socials_trigger_news_MessageBuilder
   extends tx_t3socials_trigger_MessageBuilder {
   protected function buildMessage(
      tx_t3socials_models_Message $message,
      tx_t3socials_models_Base $model
   ) {
      $message->setHeadline($model->getTitle());
      $message->setIntro($model->getShort());
      $message->setMessage($model->getBodytext());
      $message->setData($model);
      return $message;
   }
}
~~~~

Wenn beim Bauen der Message spezielle Anpassungen für Netzwerke oder deren Konfiguration durchgeführt werden müssen, kann dies über die Methode prepareMessageForNetwork geschehen.

Ein Beispiel dafür ist die Generierung der URL. Die URL kann im Falle einer News automatisiert nur anhand einer TypoScript Konfiguration erzeugt werden, da wir auf eine Detailseite mit dem tt\_news Detail Plugin verlinken müssen.

Die Konfiguration eines Links wird im Abschnitt accounts erklärt.

Wie im MessageBuilder auf diese Konfiguration zugegriffen und eine dynamische URL zusammen gebaut werden kann, zeigt folgendes Beispiel:

~~~~ {.sourceCode .php}
tx_rnbase::load('tx_t3socials_trigger_MessageBuilder');
class tx_t3socials_trigger_news_MessageBuilder
   extends tx_t3socials_trigger_MessageBuilder {

   ...

   public function prepareMessageForNetwork(
      tx_t3socials_models_IMessage &$message,
      tx_t3socials_models_Network $network,
      tx_t3socials_models_TriggerConfig $trigger
   ) {
      $confId = $network->getNetwork() . '.' . $trigger->getTriggerId() . '.';

      tx_rnbase::load('tx_rnbase_util_Misc');
      $tsfe = tx_rnbase_util_Misc::prepareTSFE();

      $news = $message->getData();
      $config = $network->getConfigurations();
      $link = $config->createLink();
      // tx_ttnews[tt_news]
      $link->designator('tx_ttnews');
      // Den Link anhand des Typoscripten initialisieren
      // und den Parameter für den News Datensatz mitgeben
      $link->initByTS($config, $confId . 'link.show.', array('tt_news' => $news->getUid()));
      // wenn nicht anders konfiguriert, immer eine absoplute url setzesetzen!
      if (!$config->get($confId . 'link.show.absurl')) {
         $link->setAbsUrl(TRUE);
      }
      // wenn realURL oder eine ähnliche Extension installiert ist
      // müssen wir uns im BE um die Umwandlung der URL kümmern!
      tx_rnbase::load('tx_t3socials_util_Link');
      $url = tx_t3socials_util_Link::getRealUrlAbsUrlForLink($link);

      $message->setUrl($url);
   }
}
~~~~

Registrierung
-------------

Um ein Trigger zu registrieren, wird die Konfiguration benötigt. Diese Konfiguration muss über die ext\_localconf.php bei T3 SOCIALS registriert werden.

Mit der Registrierung werden alle Änderungen an der, in der Konfiguration angegebenen Tabelle, überwacht.

Je nach Konfiguration der angelegten Accounts und Status des Datensatzes, wird dieser nun automatisch an die Netzwerke verteilt oder man erhält nach dem Speichern eine Infomeldung, das der Datensatz über die manual-dispatch-news verteilt werden kann.

Beispiel der Registrierung:

~~~~ {.sourceCode .php}
/* *** **************** *** *
 * *** Register Trigger *** *
 * *** **************** *** */
tx_rnbase::load('tx_t3socials_trigger_Config');
tx_t3socials_trigger_Config::registerTrigger(
   'tx_t3socials_trigger_news_TriggerConfig'
);
~~~~
