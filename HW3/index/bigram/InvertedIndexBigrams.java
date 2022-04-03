import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.Text;
import org.apache.hadoop.mapreduce.Job;
import org.apache.hadoop.mapreduce.Mapper;
import org.apache.hadoop.mapreduce.Reducer;
import org.apache.hadoop.mapreduce.lib.input.FileInputFormat;
import org.apache.hadoop.mapreduce.lib.output.FileOutputFormat;
import java.util.HashMap;
import java.util.stream.Collectors;
import java.io.IOException;
import java.util.StringTokenizer;

public class InvertedIndexBigrams {
 
  public static class TokenizerMapper extends Mapper<Object, Text, Text, Text>{
   
	Text docID = new Text();
    Text word = new Text(); 

    public void map(Object key, Text value, Context context) throws IOException, InterruptedException {
     
      String[] docTextArray = value.toString().split("\t", 2);
      String docIDStr = docTextArray[0];
      String docTextStr = docTextArray[1].toLowerCase();
      
      docID.set(docIDStr);

      String formattedText = docTextStr.replaceAll("[^a-z\\s]"," ").replaceAll("\\s+"," ");

      StringTokenizer tokenizer = new StringTokenizer(formattedText);
      String prevWord = tokenizer.nextToken();
      while (tokenizer.hasMoreTokens()) { 
    	String currWord = tokenizer.nextToken();
        word.set(prevWord + " " + currWord);
        context.write(word, docID);
        prevWord = currWord;
      }
    }
  }
  
  public static class WordReducer extends Reducer<Text,Text,Text,Text> {

    private Text result = new Text();
    
    public void reduce(Text key, Iterable<Text> values, Context context) throws IOException, InterruptedException {

      HashMap<String,Integer> wordFrequencyMap = new HashMap<String,Integer>();

      for (Text val : values) {  
    	  String docIDStr = val.toString();
    	  if(!wordFrequencyMap.containsKey(docIDStr)) {
    		  wordFrequencyMap.put(docIDStr, 1);
    	  } else {
    		  Integer count = wordFrequencyMap.get(docIDStr);
    		  count += 1;
    		  wordFrequencyMap.put(docIDStr, count);
    	  }
      }
      
      String res = "";

      for (String docID : wordFrequencyMap.keySet()){
        res += docID + ":" + Integer.toString(wordFrequencyMap.get(docID)) + " ";
      }
 
      res = res.trim();
      result.set(res);
      context.write(key, result);
    }
  }

  public static void main(String[] args) throws Exception {
    Configuration conf = new Configuration();
    Job job = Job.getInstance(conf, "Inverted_Index_Job");
    job.setJarByClass(InvertedIndexBigrams.class);
    job.setMapperClass(TokenizerMapper.class);
    job.setReducerClass(WordReducer.class);
    job.setOutputKeyClass(Text.class);
    job.setOutputValueClass(Text.class);
    FileInputFormat.addInputPath(job, new Path(args[0]));
    FileOutputFormat.setOutputPath(job, new Path(args[1]));
    System.exit(job.waitForCompletion(true) ? 0 : 1);
  }
}